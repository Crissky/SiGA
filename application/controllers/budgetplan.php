<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("usuario.php");
require_once(APPPATH."/constants/GroupConstants.php");
require_once(APPPATH."/controllers/security/session/SessionManager.php");

class Budgetplan extends CI_Controller {

	public function index() {

		$this->load->model("budgetplan_model");
		$this->load->model("course_model");

		$budgetplans = $this->budgetplan_model->all();
		foreach ($budgetplans as $key => $b) {
			$budgetplans[$key]['status'] = $this->budgetplan_model->getBudgetplanStatus($b['status']);
			$course = $this->course_model->getCourseById($b['course_id']);
			$budgetplans[$key]['course'] = $course ? $course['course_name'] : "Nenhum";
		}

		$status_options = $this->db->get("budgetplan_status")->result_array();
		$status = array();
		foreach ($status_options as $s) {
			array_push($status, $s['description']);
		}

		$this->load->model('course_model');
		$courses_options = $this->course_model->getAllCourses();
		$courses = array();

		if($courses_options !== FALSE){

			foreach ($courses_options as $c) {
				$courses[$c['id_course']] = $c['course_name'];
			}
		}
		else{
			$courses = FALSE;
		}

		$user = new Usuario();
		$teachers = $user->getUsersOfGroup(GroupConstants::TEACHER_GROUP_ID);

		if($teachers !== FALSE){

			foreach($teachers as $teacher){
				$managers[$teacher['id']] = $teacher['name'];
			}
		}else{

			$managers = FALSE;
		}

		$this->load->helper(array("currency"));
		$data = array(
			"budgetplans" => $budgetplans,
			"status" => $status,
			"courses" => $courses,
			"managers" => $managers
		);

		loadTemplateSafelyByPermission(PermissionConstants::BUDGETPLAN_PERMISSION, 'budgetplan/index', $data);
	}

	public function save() {


		$budgetplanName = $this->input->post("budgetplan_name");
		$manager = $this->input->post("manager");
		$course = $this->input->post("course");
		$amount = $this->input->post("amount");
		$status = $this->input->post("status") + 1;

		$budgetplan = array(
			'amount' => $amount,
			'status' => $status,
			'balance' => $amount,
			'budgetplan_name' => $budgetplanName
		);

		if ($course) {
			$budgetplan['course_id'] = $course;
		}

		if($manager !== 0){
			$budgetplan['manager'] = $manager;
		}

		$this->load->model('budgetplan_model');

		if ($this->budgetplan_model->save($budgetplan)) {				

			$status = "success";
			$message = "Novo Plano orçamentário cadastrado";
		} 
		else {
			$status = "danger";
			$message = "Houve algum erro. Plano orçamentário não cadastrado";
		}
		
		$session = SessionManager::getInstance(); 
		$session->showFlashMessage($status, $message);
		redirect(PermissionConstants::BUDGETPLAN_PERMISSION);
	}

	public function edit($id) {

		$this->load->model('budgetplan_model');
		$this->load->model('course_model');
		$this->load->model('expense_model');

		$budgetplan = $this->budgetplan_model->get('id', $id);

		$status_options = $this->db->get("budgetplan_status")->result_array();
		$status = array();
		foreach ($status_options as $s) {
			array_push($status, $s['description']);
		}

		$courses_options = $this->course_model->getAllCourses();
		$courses = array("Nenhum");
		foreach ($courses_options as $c) {
			$courses[$c['id_course']] =  $c['course_name'];
		}

		$disable_amount   = $budgetplan['status'] == 3 || $budgetplan['status'] == 4 ? "readonly" : "";
		$disable_spending = $budgetplan['status'] == 4 ? "readonly" : "";

		$user = new Usuario();
		$teachers = $user->getUsersOfGroup(GroupConstants::TEACHER_GROUP_ID);

		if($teachers !== FALSE){

			foreach($teachers as $teacher){
				$managers[$teacher['id']] = $teacher['name'];
			}
		}else{

			$managers = FALSE;
		}

		$this->load->helper(array("currency"));
		$data = array(
			'budgetplan' => $budgetplan,
			'status' => $status,
			'courses' => $courses,
			'disable_amount' => $disable_amount,
			'disable_spending' => $disable_spending,
			'managers' => $managers
		);

		loadTemplateSafelyByPermission(PermissionConstants::BUDGETPLAN_PERMISSION, 'budgetplan/edit', $data);
	}

	public function update() {

		$id = $this->input->post("budgetplan_id");
		$budgetplanName = $this->input->post("budgetplan_name");
		$manager = $this->input->post("manager");
		$course = $this->input->post("course");
		$amount = $this->input->post("amount");
		$status = $this->input->post("status") + 1;
		$spending = $this->input->post("spending");
		$continue = $this->input->post("continue");

		if (!$continue) {
			redirect(PermissionConstants::BUDGETPLAN_PERMISSION."/{$id}");
		}

		$budgetplan = array(
			'id' => $id,
			'amount' => $amount,
			'status' => $status,
			'spending' => $spending,
			'balance' => $amount - $spending,
			'budgetplan_name' => $budgetplanName
		);

		if ($course) {
			$budgetplan['course_id'] = $course;
		}

		if($manager !== 0){
			$budgetplan['manager'] = $manager;
		}

		$this->load->model('budgetplan_model');
		
		
		if ($this->budgetplan_model->update($budgetplan)) {
			$status = "success";
			$message = "Plano orçamentário alterado";
		} else {
			$status = "danger";
			$message = "Houve algum erro. Tente novamente";
		}

		$session = SessionManager::getInstance();
		$session->showFlashMessage($status, $message);

		redirect(PermissionConstants::BUDGETPLAN_PERMISSION."/{$id}");
	}

	public function delete() {

		$id = $this->input->post("budgetplan_id");
		$this->load->model('budgetplan_model');
		$this->load->model('expense_model');

		$expenses = $this->budgetplan_model->getExpenses($id);
		foreach ($expenses as $expense) {
			$this->expense_model->delete($expense['id']);
		}

		$session = SessionManager::getInstance();

		if ($this->budgetplan_model->delete($id)) {
			$session->showFlashMessage("danger", "Plano orçamentário foi removido");
		}

		redirect(PermissionConstants::BUDGETPLAN_PERMISSION);
	}

	public function budgetplanExpenses($budgetplanId){

		$this->load->model("budgetplan_model");
		$this->load->model('expense_model');

		$budgetplan = $this->budgetplan_model->get('id', $budgetplanId);

		$expenses = $this->budgetplan_model->getExpenses($budgetplan);
		foreach ($expenses as $key => $expense) {
			$type = $this->expense_model->getExpenseType($expense['expense_type_id']);
			$expenses[$key]['expense_type_id'] = $type['id'];
			$expenses[$key]['expense_type_description'] = $type['description'];
		}

		$this->load->helper(array("currency"));

		$data = array(
			'budgetplan' => $budgetplan,
			'expenses' => $expenses
		);

		loadTemplateSafelyByPermission(PermissionConstants::BUDGETPLAN_PERMISSION, 'budgetplan/budgetplan_expenses', $data);
	}

	public function deleteBudgetplanByCourseId($courseId){

		 $this->load->model("budgetplan_model");
		 $this->budgetplan_model->deleteByCourseId($courseId);
	}

}

/* End of file budgetplan.php */
/* Location: ./application/controllers/budgetplan.php */ ?>