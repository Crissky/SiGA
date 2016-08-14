<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH."/constants/EnrollmentConstants.php");

class Request_model extends CI_Model {

	public $TABLE = "student_request";

	public function saveNewRequest($student, $course, $semester, $mastermindApproval = 0){

		$requestData = array(
			'id_student' => $student,
			'id_course' => $course,
			'id_semester' => $semester,
			'request_status' => EnrollmentConstants::REQUEST_INCOMPLETE_STATUS,
			'mastermind_approval' => $mastermindApproval
		);

		$registeredRequest = $this->getRequest($requestData);

		if($registeredRequest !== FALSE){
			$requestId = $registeredRequest['id_request'];
		}else{

			$this->db->insert('student_request', $requestData);

			$foundRequest = $this->getRequest($requestData);

			if($foundRequest !== FALSE){
				$requestId = $foundRequest['id_request'];
			}else{
				$requestId = FALSE;
			}
		}

		return $requestId;
	}

	public function saveDisciplineRequest($requestId, $idOfferDiscipline, $status, $mastermindApproval = 0, $isUpdate=FALSE){

		$requestDiscipline = array(
			'id_request' => $requestId,
			'discipline_class' => $idOfferDiscipline,
			'status' => $status,
			'mastermind_approval' => $mastermindApproval,
			'is_update' => $isUpdate
		);

		$this->db->insert('request_discipline', $requestDiscipline);

		$foundRequest = $this->getRequestDisciplines($requestDiscipline);

		if($foundRequest !== FALSE){
			$wasSaved = TRUE;
		}else{
			$wasSaved = FALSE;
		}

		return $wasSaved;
	}

	public function removeDisciplineRequest($requestId, $idOfferDiscipline){

		$where = array(
			'id_request' => $requestId,
			'discipline_class' => $idOfferDiscipline
		);

		$request = $this->getRequestDiscipline($where);

		$this->db->trans_start();
		if(!$request['is_update']){
			$this->load->model("secretary/offer_model");
			$this->offer_model->addOneVacancy($idOfferDiscipline);
		}
		$this->db->delete("request_discipline", $where);
		$this->db->trans_complete();
	}

	private function getRequestDiscipline($requestData){
		return $this->get($requestData, FALSE, TRUE, FALSE, "request_discipline");
	}

	public function approveAllRequest($requestId){

		$wasApproved = $this->changeAllRequest($requestId, EnrollmentConstants::APPROVED_STATUS, EnrollmentConstants::REQUESTING_AREA_SECRETARY);

		return $wasApproved;
	}

	public function refuseAllRequest($requestId){

		$wasRefused = $this->changeAllRequest($requestId, EnrollmentConstants::REFUSED_STATUS, EnrollmentConstants::REQUESTING_AREA_SECRETARY);

		return $wasRefused;
	}

	public function mastermindApproveAllCurrentStudentRequest($requestId){

		$wasApproved = $this->changeAllRequest($requestId, EnrollmentConstants::APPROVED_STATUS, EnrollmentConstants::REQUESTING_AREA_MASTERMIND);

		$this->requestDisciplineApproval(EnrollmentConstants::REQUESTING_AREA_MASTERMIND, TRUE, $requestId);

		return $wasApproved;
	}

	public function mastermindRefuseAllCurrentStudentRequest($requestId){

		$wasRefused = $this->changeAllRequest($requestId, EnrollmentConstants::REFUSED_STATUS, EnrollmentConstants::REQUESTING_AREA_MASTERMIND);

		$this->requestDisciplineApproval(EnrollmentConstants::REQUESTING_AREA_MASTERMIND, FALSE, $requestId);

		return $wasRefused;
	}

	private function changeAllRequest($requestId, $newStatus, $requestingArea){

		switch($requestingArea){
			case EnrollmentConstants::REQUESTING_AREA_SECRETARY:
				$disciplinesConditions = array(
					'id_request' => $requestId,
					'mastermind_approval' => EnrollmentConstants::DISCIPLINE_APPROVED_BY_MASTERMIND
				);
				break;

			default:
				$disciplinesConditions = array(
					'id_request' => $requestId
				);
				break;
		}

		$requestDisciplines = $this->getRequestDisciplines($disciplinesConditions);

		if($requestDisciplines !== FALSE){

			foreach($requestDisciplines as $requestedDiscipline){

				if($requestedDiscipline['status'] !== EnrollmentConstants::NO_VACANCY_STATUS){

					$this->changeRequestDisciplineStatus($requestId, $requestedDiscipline['discipline_class'], $newStatus, $requestedDiscipline['requested_on']);

					if($newStatus === EnrollmentConstants::APPROVED_STATUS){
						$isToApprove = TRUE;
					}else{
						$isToApprove = FALSE;
					}

					$this->requestDisciplineApproval(EnrollmentConstants::REQUESTING_AREA_SECRETARY, $isToApprove, $requestId, $requestedDiscipline['discipline_class']);
				}
			}

			$this->checkRequestGeneralStatus($requestId, $requestingArea);
			$wasChanged = TRUE;

		}else{

			$wasChanged = FALSE;
		}

		return $wasChanged;
	}

	public function finalizeRequestToMastermind($requestId){

		$this->db->where('id_request', $requestId);
		$this->db->update('student_request', array('mastermind_approval' => EnrollmentConstants::REQUEST_APPROVED_BY_MASTERMIND));

		$foundRequest = $this->getRequest(array('id_request' => $requestId, 'mastermind_approval' => EnrollmentConstants::REQUEST_APPROVED_BY_MASTERMIND));

		$wasFinalized = $foundRequest !== FALSE;

		return $wasFinalized;
	}

	public function finalizeRequestSecretary($requestId){

		$this->updateVacancies($requestId);

		$wasApproved = $this->secretaryApproval($requestId);

		$this->checkRequestGeneralStatus($requestId);

		return $wasApproved;
	}

	private function updateVacancies($requestId){

		$requestDisciplines = $this->getRequestDisciplinesById($requestId);

		if($requestDisciplines !== FALSE){

			$this->load->model("secretary/offer_model");

			$this->db->trans_start();

			foreach($requestDisciplines as $discipline){
				// Add one vacancy to each offer discipline class refused that was not added later as a request update
				if($discipline['status'] === EnrollmentConstants::REFUSED_STATUS
					&& !$discipline['is_update']){
					$this->offer_model->addOneVacancy($discipline['discipline_class']);

				// Add one vacancy to each offer discipline class refused
				}elseif($discipline['status'] === EnrollmentConstants::APPROVED_STATUS
					&& $discipline['is_update']){
					$this->offer_model->subtractOneVacancy($discipline['discipline_class']);
				}
			}

			$this->db->trans_complete();
		}
	}

	private function secretaryApproval($requestId){

		$this->db->where('id_request', $requestId);
		$this->db->update('student_request', array('secretary_approval' => EnrollmentConstants::REQUEST_APPROVED_BY_SECRETARY));

		$foundRequest = $this->getRequest(array('id_request' => $requestId, 'secretary_approval' => EnrollmentConstants::REQUEST_APPROVED_BY_SECRETARY));

		$wasFinalized = $foundRequest !== FALSE;

		return $wasFinalized;
	}

	private function checkStudentHasRequest($studentId){
		$this->db->select('id_request');
		$this->db->where('id_student', $studentId);
		$this->db->from('student_request');
		$hasRequest = $this->db->get()->result_array();

		$hasRequest = checkArray($hasRequest);

		return $hasRequest;
	}

	private function requestDisciplineApproval($requestingArea, $isToApprove, $requestId, $idOfferDiscipline = FALSE){

		if($idOfferDiscipline !== FALSE){

			$whereClause = array(
				'id_request' => $requestId,
				'discipline_class' => $idOfferDiscipline
			);
		}else{
			// In this case is to update all the disciplines of a request
			$whereClause = array(
				'id_request' => $requestId
			);
		}

		switch($requestingArea){
			case EnrollmentConstants::REQUESTING_AREA_MASTERMIND:
				if($isToApprove){
					$toUpdate = array(
						'mastermind_approval' => EnrollmentConstants::DISCIPLINE_APPROVED_BY_MASTERMIND
					);
				}else{
					$toUpdate = array(
						'mastermind_approval' => EnrollmentConstants::DISCIPLINE_REFUSED_BY_MASTERMIND
					);
				}
				break;

			case EnrollmentConstants::REQUESTING_AREA_SECRETARY:
				if($isToApprove){
					$toUpdate = array(
						'secretary_approval' => EnrollmentConstants::DISCIPLINE_APPROVED_BY_SECRETARY
					);
				}else{
					$toUpdate = array(
						'secretary_approval' => EnrollmentConstants::DISCIPLINE_REFUSED_BY_SECRETARY
					);
				}
				break;

			default:
				$toUpdate = array();
				break;
		}

		$this->db->where($whereClause);
		$this->db->update('request_discipline', $toUpdate);
	}

	public function approveRequestedDiscipline($requestId, $idOfferDiscipline, $requestingArea, $requestDate=NULL){

		$this->db->trans_start();

		$wasApproved = $this->changeRequestDisciplineStatus($requestId, $idOfferDiscipline, EnrollmentConstants::APPROVED_STATUS, $requestDate);

		$this->checkRequestGeneralStatus($requestId, $requestingArea);

		$this->requestDisciplineApproval($requestingArea, TRUE, $requestId, $idOfferDiscipline);

		$this->db->trans_complete();

		$transaction_status = $this->db->trans_status();

		if($transaction_status === FALSE){
			$wasApproved = FALSE;
		}

		return $wasApproved;
	}

	public function refuseRequestedDiscipline($requestId, $idOfferDiscipline, $requestingArea, $requestDate=NULL){

		$this->db->trans_start();

		$wasRefused = $this->changeRequestDisciplineStatus($requestId, $idOfferDiscipline, EnrollmentConstants::REFUSED_STATUS, $requestDate);

		$this->checkRequestGeneralStatus($requestId, $requestingArea);

		$this->requestDisciplineApproval($requestingArea, FALSE, $requestId, $idOfferDiscipline);

		$this->db->trans_complete();

		$transaction_status = $this->db->trans_status();

		if($transaction_status === FALSE){
			$wasRefused = FALSE;
		}

		return $wasRefused;
	}

	private function checkRequestGeneralStatus($requestId, $requestingArea=""){

		$foundRequest = $this->getRequest(array('id_request' => $requestId));

		if($foundRequest !== FALSE){

			$wasAllApproved = $this->checkIfRequestWasAllApprovedOrRefused($requestId, EnrollmentConstants::APPROVED_STATUS);
			$wasAllRefused = $this->checkIfRequestWasAllApprovedOrRefused($requestId, EnrollmentConstants::REFUSED_STATUS);
			$hasPreEnrolled = $this->checkIfRequestHasPreEnrolled($requestId);

			$requestIsFinalizedBySecretary = $foundRequest['secretary_approval'] == EnrollmentConstants::REQUEST_APPROVED_BY_SECRETARY;

			if($wasAllApproved){
				if($requestIsFinalizedBySecretary){
					$status = EnrollmentConstants::ENROLLED_STATUS;
				}else{

					$status = EnrollmentConstants::REQUEST_ALL_APPROVED_STATUS;
					if(!empty($requestingArea) && $requestingArea === EnrollmentConstants::REQUESTING_AREA_MASTERMIND){
						// If mastermind approved all request, finalize it
						$this->finalizeRequestToMastermind($requestId);
					}
				}
			}else if($wasAllRefused){
				$status = EnrollmentConstants::REQUEST_ALL_REFUSED_STATUS;
			}else if($hasPreEnrolled){
				$status = EnrollmentConstants::REQUEST_INCOMPLETE_STATUS;
			}else{
				$status = EnrollmentConstants::REQUEST_PARTIALLY_APPROVED_STATUS;
			}

			$this->changeRequestGeneralStatus($requestId, $status);
		}
	}

	private function changeRequestGeneralStatus($requestId, $newStatus){

		$this->db->where('id_request', $requestId);
		$this->db->update('student_request', array('request_status' => $newStatus));
	}

	private function checkIfRequestHasPreEnrolled($requestId){

		$requestDisciplines = $this->getRequestDisciplinesById($requestId);

		if($requestDisciplines !== FALSE){

			$hasPreEnrolled = FALSE;
			foreach($requestDisciplines as $requestedDiscipline){

				if($requestedDiscipline['status'] === EnrollmentConstants::PRE_ENROLLED_STATUS){
					$hasPreEnrolled = TRUE;
					break;
				}
			}
		}else{
			$hasPreEnrolled = FALSE;
		}

		return $hasPreEnrolled;
	}

	private function checkIfRequestWasAllApprovedOrRefused($requestId, $statusToCheck){

		$requestDisciplines = $this->getRequestDisciplinesById($requestId);

		if($requestDisciplines !== FALSE){

			$disciplinesWithStatus = 0;

			// Check if all disciplines has the given status
			foreach($requestDisciplines as $requestedDiscipline){

				if($requestedDiscipline['status'] === $statusToCheck){
					$disciplinesWithStatus++;
				}
			}

			$quantityOfDisciplines = sizeof($requestDisciplines);

			// In this case all disciplines as approved
			if($quantityOfDisciplines === $disciplinesWithStatus){
				$wasAll = TRUE;
			}else{
				$wasAll = FALSE;
			}

		}else{
			$wasAll = FALSE;
		}

		return $wasAll;
	}

	private function changeRequestDisciplineStatus($requestId, $idOfferDiscipline, $newStatus, $requestDate=NULL, $request=array()){

		if(empty($request)){
			$this->db->where("id_request", $requestId);
			$this->db->where("discipline_class", $idOfferDiscipline);
			if($requestDate !== NULL){
				// The date comes encoded from the URL
				$requestDate = urldecode($requestDate);
				$this->db->where(array("requested_on" => $requestDate));
			}
		}else{
			$this->db->where($request);
		}
		$this->db->update('request_discipline', array('status' => $newStatus));

		if(empty($request)){
			$requestDisciplineData = array(
				'id_request' => $requestId,
				'discipline_class' => $idOfferDiscipline,
				'status' => $newStatus
			);

			if($requestDate !== NULL){
				$requestDisciplineData['requested_on'] = $requestDate;
			}
		}else{
			$requestDisciplineData = $request;
			$requestDisciplineData['status'] = $newStatus;
		}

		$foundRequestDiscipline = $this->getRequestDisciplines($requestDisciplineData);

		$wasChanged = $foundRequestDiscipline !== FALSE;

		return $wasChanged;
	}

	public function getStudentRequests($courseId, $semesterId, $studentIds){

		$this->db->select("student_request.*");
		$this->db->distinct();
		$this->db->from("student_request");
		$this->db->join("request_discipline", "student_request.id_request = request_discipline.id_request");
		$this->db->where("student_request.id_course", $courseId);
		$this->db->where("student_request.id_semester", $semesterId);

		$ids = $studentIds;

		$whereClause = "(";
		foreach($studentIds as $key => $studentId){

			if(hasNext($ids)){
				unset($ids[$key]);
				$whereClause = $whereClause."student_request.id_student = {$studentId} OR ";
			}else{
				$whereClause = $whereClause." student_request.id_student = {$studentId}";
			}
		}
		$whereClause = $whereClause.")";

		$this->db->where($whereClause);
		$this->db->order_by("request_status", "asc");

		$foundRequest = $this->db->get()->result_array();

		$foundRequest = checkArray($foundRequest);

		return $foundRequest;
	}

	public function getRequestDisciplinesById($requestId){

		$disciplines = $this->getRequestDisciplines(array('id_request' => $requestId));

		return $disciplines;
	}

	private function getRequestDisciplines($requestDisciplineData){

		$foundRequestDiscipline = $this->db->get_where('request_discipline', $requestDisciplineData)->result_array();

		$foundRequestDiscipline = checkArray($foundRequestDiscipline);

		return $foundRequestDiscipline;
	}

	public function getRequestCourseId($requestId){

		$this->db->select('id_course');
		$this->db->from('student_request');
		$this->db->where('id_request', $requestId);
		$course_id = $this->db->get()->row_array();
		$course_id = checkArray($course_id);

		return $course_id;

	}

	public function getUserRequestDisciplines($userId, $courseId, $semesterId){

		$requestData = array(
			'id_student' => $userId,
			'id_course' => $courseId,
			'id_semester' => $semesterId
		);

		$request = $this->request_model->getRequest($requestData);

		if($request !== FALSE){

			$requestStatus = $request['request_status'];

			$classes = $this->getRequestDisciplinesClasses($request['id_request']);

			$requestDisciplinesClasses = array(
				'requestStatus' => $requestStatus,
				'requestDisciplinesClasses' => $classes
			);

		}else{
			$requestDisciplinesClasses = FALSE;
		}

		return $requestDisciplinesClasses;
	}

	public function getMastermindMessage($studentId, $courseId, $semesterId){
		$requestData = array(
				'id_student' => $studentId,
				'id_course' => $courseId,
				'id_semester' => $semesterId
		);

		$request = $this->request_model->getRequest($requestData);

		if($request !== FALSE){
			$message = $this->getMastermindMessageForStudent($studentId, $request['id_request']);
		}else{
			$message = 'Seu Orientador não deixou mensagem.';
		}

		return $message;
	}

	public function getRequestDisciplinesClasses($requestId){

		$this->db->select('offer_discipline.*, request_discipline.*');
		$this->db->from('request_discipline');
		$this->db->join('offer_discipline', "request_discipline.discipline_class = offer_discipline.id_offer_discipline");
		$this->db->where('request_discipline.id_request', $requestId);
		$foundClasses = $this->db->get()->result_array();

		$foundClasses = checkArray($foundClasses);

		return $foundClasses;
	}

	private function getMastermindMessageForStudent($studentId, $requestId){
		$where = array(
			'id_request' => $requestId,
			'id_student' => $studentId
		);
		$mastermindMessage = $this->db->get_where('mastermind_message', $where)->row_array();

		$mastermindMessage = checkArray($mastermindMessage);

		if ($mastermindMessage){
			$message = $mastermindMessage['message'];
		}else{
			$message = 'Seu Orientador não deixou mensagem.';
		}

		return $message;
	}

	public function getRequest($requestData){

		$foundRequest = $this->db->get_where('student_request', $requestData)->row_array();

		$foundRequest = checkArray($foundRequest);

		return $foundRequest;
	}

	public function getCourseRequests($courseId, $semesterId){

		$this->db->select("student_request.*");
		$this->db->distinct();
		$this->db->from("student_request");
		$this->db->join("request_discipline", "student_request.id_request = request_discipline.id_request");
		$this->db->where("student_request.id_course", $courseId);
		$this->db->where("student_request.id_semester", $semesterId);
		$this->db->order_by("request_status", "asc");

		$foundRequest = $this->db->get()->result_array();

		$foundRequest = checkArray($foundRequest);

		return $foundRequest;
	}

	public function getMastermindStudentRequest($studentId, $semesterId){

		$this->db->select("student_request.*");
		$this->db->distinct();
		$this->db->from("student_request");
		$this->db->join("request_discipline", "student_request.id_request = request_discipline.id_request");
		$this->db->where("student_request.id_student", $studentId);
		$this->db->where("student_request.id_semester", $semesterId);
		$this->db->order_by("request_status", "asc");

		$foundRequest = $this->db->get()->result_array();

		$foundRequest = checkArray($foundRequest);

		return $foundRequest;
	}

	public function saveMastermindMessage($mastermindId, $requestId, $message){

		$messageData = array(
			'id_mastermind' => $mastermindId,
			'id_request' => $requestId,
			'message' => $message
		);

		$messageExist = $this->checkExistingMessage($mastermindId, $requestId);

		if($messageExist){
			$savedMessage = $this->updateMessageInDb($messageData);
		}else{
			$savedMessage = $this->insertMessageInDb($messageData);
		}

		return $savedMessage;
	}

	private function checkExistingMessage($mastermindId, $requestId){

		$messageData = array(
			'id_mastermind' => $mastermindId,
			'id_request' => $requestId
		);

		$existingMessage = $this->db->get_where('mastermind_message', $messageData)->row_array();

		$existingMessage = checkArray($existingMessage);

		if($existingMessage !== FALSE){
			if($existingMessage['message'] !== NULL){
				$thereIsMessage = TRUE;
			}else{
				$thereIsMessage = FALSE;
			}
		}else{
			$thereIsMessage = FALSE;
		}

		return $thereIsMessage;
	}

	private function updateMessageInDb($messageData){

		$where = array(
			'id_mastermind'=> $messageData['id_mastermind'],
			'id_request'=> $messageData['id_request']
		);

		$this->db->where($where);
		$updatedMessageData = $this->db->update('mastermind_message', $messageData);

		return $updatedMessageData;
	}

	private function insertMessageInDb($messageData){

		$savedMessage = $this->db->insert('mastermind_message', $messageData);

		return $savedMessage;
	}
}
