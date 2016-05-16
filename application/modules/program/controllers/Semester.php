<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(MODULESPATH."auth/exception/LoginException.php");
require_once(MODULESPATH."/auth/controllers/SessionManager.php");

class Semester extends MX_Controller {

	public function getCurrentSemester(){

		$this->load->model('semester_model');

		$currentSemester = $this->semester_model->getCurrentSemester();

		return $currentSemester;
	}

	public function saveSemester() {
		
		$session = getSession();
		$loggedUserData = $session->getUserData();
		$loggedUserLogin = $loggedUserData->getLogin();
		$password = $this->input->post('password');
		
		$this->load->model('auth/usuarios_model');
		$this->load->model('program/semester_model');

		try{

			$user = $this->usuarios_model->validateUser($loggedUserLogin, $password);
 
			$accessGranted = sizeof($user) > 0;

			if($accessGranted){
				
				$semesterId = $this->input->post('current_semester_id') + 1;
				
				$wasUpdated = $this->semester_model->updateCurrentSemester($semesterId);

				if($wasUpdated){
					$session->showFlashMessage("success", "Semestre atual alterado");
				}else{
					$session->showFlashMessage("danger", "Não foi possível alterar o semestre atual.");
				}
				
				redirect('secretary/offer/offerList');
			}

		}catch(LoginException $caughtException){
			$session->showFlashMessage("danger", "Falha na autenticação.");
			redirect('/secretary/offer/offerList');
		}
	}

}
