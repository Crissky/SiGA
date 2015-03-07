<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once('offer.php');
require_once('semester.php');
require_once('temporaryrequest.php');

class Request extends CI_Controller {

	public function studentEnrollment($courseId, $userId){

		$this->load->model('request_model');

		$semester = new Semester();
		$currentSemester = $semester->getCurrentSemester();

		$temporaryRequest = new TemporaryRequest();
		$disciplinesToRequest = $temporaryRequest->getUserTempRequest($userId, $courseId, $currentSemester['id_semester']);

		$thereIsDisciplinesToRequest = $disciplinesToRequest !== FALSE;

		$data = array(
			'semester' => $currentSemester,
			'courseId' => $courseId,
			'userId' => $userId,
			'disciplinesToRequest' => $disciplinesToRequest,
			'thereIsDisciplinesToRequest' => $thereIsDisciplinesToRequest,
		);

		$requestForSemester = $this->getUserRequestDisciplines($userId, $courseId, $currentSemester['id_semester']);
		if($requestForSemester !== FALSE){

			$data['requestDisciplinesClasses'] = $requestForSemester['requestDisciplinesClasses'];
			
			switch($requestForSemester['requestStatus']){
				case 'incomplete':
					$requestStatus = "Incompleta (Aguardar aprovação do coordenador)";
					break;
				default:
					$requestStatus = "-";
					break;
			}

			$data['requestStatus'] = $requestStatus;
		}else{
			$data['requestDisciplinesClasses'] = FALSE;
			$data['requestStatus'] = FALSE;
		}

		loadTemplateSafelyByGroup("estudante", 'request/enrollment_request', $data);
	}

	private function getUserRequestDisciplines($userId, $courseId, $semesterId){

		$this->load->model('request_model');
		
		$requestDisciplines = $this->request_model->getUserRequestDisciplines($userId, $courseId, $semesterId);

		return $requestDisciplines;
	}

	private function getRequest($requestData){

		$this->load->model('request_model');
		
		$request = $this->request_model->getRequest($requestData);

		return $request;	
	}

	public function receiveStudentRequest($userRequest){

		if($userRequest !== FALSE){

			// Requisition ids
			$student = $userRequest[0]['id_student'];
			$course = $userRequest[0]['id_course'];
			$semester = $userRequest[0]['id_semester'];

			$requestId = $this->saveNewRequest($student, $course, $semester);

			if($requestId !== FALSE){

				define("MIN_VACANCY_QUANTITY_TO_ENROLL", 1);
				define("PRE_ENROLLED_STATUS", "pre_enrolled");
				define("NO_VACANCY_STATUS", "no_vacancy");


				$this->load->model('request_model');

				$offer = new Offer();

				foreach($userRequest as $tempRequest){
					
					$idOfferDiscipline = $tempRequest['discipline_class'];

					$class = $offer->getOfferDisciplineById($idOfferDiscipline);
					$currentVacancies = $class['current_vacancies'];

					// If there is vacancy, enroll student
					if($currentVacancies >= MIN_VACANCY_QUANTITY_TO_ENROLL){

						/**
							CHECAR RETORNO
						 */
						$this->saveDisciplineRequest($requestId, $idOfferDiscipline, PRE_ENROLLED_STATUS);

					}else{
						$this->saveDisciplineRequest($requestId, $idOfferDiscipline, NO_VACANCY_STATUS);
					}
				}

				$wasReceived = $this->checkIfRequestWasSaved($userRequest, $requestId);

			}else{
				$wasReceived = FALSE;
			}

		}else{
			$wasReceived = FALSE;
		}
		
		return $wasReceived;
	}

	private function checkIfRequestWasSaved($userRequest, $requestId){

		$noSavedDisciplines = array();
		$i = 0;
		$savedRequestDisciplines = $this->getRequestDisciplines($requestId);
		if($savedRequestDisciplines !== FALSE){
			$offer = new Offer();
			foreach($userRequest as $tempRequest){

				$idOfferDiscipline = $tempRequest['discipline_class'];
				$wasSaved = FALSE;
				foreach($savedRequestDisciplines as $requestDiscipline){
					
					if($requestDiscipline['discipline_class'] === $idOfferDiscipline){
						$wasSaved = TRUE;
						break;
					}
				}

				if($wasSaved){
					// Nothing to do because was saved
				}else{
					$missedDisciplineClass = $offer->getOfferDisciplineById($idOfferDiscipline);
					$noSavedDisciplines[$i] = $missedDisciplineClass;
					$i++;
				}
			}

			$wasReceived = $noSavedDisciplines;
		}else{
			$wasReceived = FALSE;
		}

		return $wasReceived;
	}

	private function getRequestDisciplines($requestId){

		$this->load->model('request_model');
		
		$disciplines = $this->request_model->getRequestDisciplinesById($requestId);

		return $disciplines;
	}

	private function saveDisciplineRequest($requestId, $idOfferDiscipline, $status){

		$this->load->model('request_model');
		
		$wasSaved = $this->request_model->saveDisciplineRequest($requestId, $idOfferDiscipline, $status);

		define("NO_VACANCY_STATUS", "no_vacancy");

		if($wasSaved){

			$canSubtract = $status !== NO_VACANCY_STATUS;

			if($canSubtract){
				
				$offer = new Offer();
				$wasSubtracted = $offer->subtractOneVacancy($idOfferDiscipline);

				if($wasSubtracted){
					$disciplineWasAdded = TRUE;

				}else{

					/**

					  In this case, the discipline might be saved but the vacancy might be not subtracted

					 */
					$disciplineWasAdded = FALSE;
				}
			}else{
				// If there is no vacancy, don't need to subtract the vacancy
				$disciplineWasAdded = TRUE;
			}
		}else{
			$disciplineWasAdded = FALSE;
		}

		return $disciplineWasAdded;
	}

	private function saveNewRequest($student, $course, $semester){

		$this->load->model('request_model');
		
		$requisitionId = $this->request_model->saveNewRequest($student, $course, $semester);

		return $requisitionId;
	}
}
