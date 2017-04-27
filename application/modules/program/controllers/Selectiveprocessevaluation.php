<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(MODULESPATH."/auth/constants/PermissionConstants.php");
require_once(MODULESPATH."/auth/constants/GroupConstants.php");
require_once(MODULESPATH."/program/constants/SelectionProcessConstants.php");

class SelectiveProcessEvaluation extends MX_Controller {

    public function __construct(){
        parent::__construct();

        $this->load->model("program/selectiveprocess_model", "process_model");
        $this->load->model("program/selectiveprocessevaluation_model", "process_evaluation_model");

        $this->load->helper("selectionprocess_helper");
    }

    public function index(){
        
        $openSelectiveProcesses = $this->process_evaluation_model->getProcessesForEvaluationByTeacher(getLoggedUserId());

        $processesPhase = $this->getProcessesPhases($openSelectiveProcesses);

        $data = array(
            'openSelectiveProcesses' => $openSelectiveProcesses,
            'processesPhase' => $processesPhase);
       
        loadTemplateSafelyByGroup(GroupConstants::TEACHER_GROUP, "program/selection_process_evaluation/index", $data);
    }

    private function getProcessesPhases($openSelectiveProcesses){

        $processesPhase = array();
        $phasesWithStatus = array(
            SelectionProcessConstants::IN_HOMOLOGATION_PHASE => SelectionProcessConstants::HOMOLOGATION_PHASE_ID, 
            SelectionProcessConstants::IN_PRE_PROJECT_PHASE => SelectionProcessConstants::PRE_PROJECT_EVALUATION_PHASE_ID, 
            SelectionProcessConstants::IN_WRITTEN_TEST_PHASE => SelectionProcessConstants::WRITTEN_TEST_PHASE_ID, 
            SelectionProcessConstants::IN_ORAL_TEST_PHASE => SelectionProcessConstants::ORAL_TEST_PHASE_ID
        );
        
        if($openSelectiveProcesses){
            foreach ($openSelectiveProcesses as $process) {
                $id = $process->getId();
                $status = getProcessStatus($process);
                $processesPhase[$id]['status'] = $status; 
                $processesPhase[$id]['canEvaluate'] = 
                    $status == SelectionProcessConstants::IN_PRE_PROJECT_PHASE ||
                    $status == SelectionProcessConstants::IN_WRITTEN_TEST_PHASE ||
                    $status == SelectionProcessConstants::IN_ORAL_TEST_PHASE ? TRUE : FALSE;
                $processesPhase[$id]['phaseId'] = isset($phasesWithStatus[$status]) ? $phasesWithStatus[$status] : FALSE;
            }
        }

        return $processesPhase;
    }

    public function showTeacherCandidates($processId, $phaseId){

        $teacherId = getLoggedUserId();

        $phaseName = getPhaseName($phaseId);
        $candidates = $this->process_evaluation_model->getTeacherCandidates($teacherId, $processId);

        $doc = $phaseId == SelectionProcessConstants::PRE_PROJECT_EVALUATION_PHASE_ID ? SelectionProcessConstants::PRE_PROJECT_DOCUMENT_ID: FALSE;

        $phasesNames = $this->getPhasesNames($candidates);
        $candidates = $this->groupCandidates($candidates);

        $data = array(
            'candidates' => $candidates,
            'phasesNames' => $phasesNames,
            'teacherId' => $teacherId,
            'doc' => $doc
        );

       
        loadTemplateSafelyByGroup(GroupConstants::TEACHER_GROUP, "program/selection_process_evaluation/evaluate", $data);
    }

    private function getPhasesNames($candidates){

        $phasesNames = array();
        if($candidates){
            foreach ($candidates as $key => $candidate) {
                $processphaseId = $candidate['id_process_phase'];
                $phasesNames[$processphaseId] = $this->process_evaluation_model->getPhaseNameByPhaseProcessId($processphaseId);
            }
        }

        return $phasesNames;
    }

    private function groupCandidates($candidates){

        $candidatesEvaluations = array();
        if($candidates){
            foreach ($candidates as $candidate) {
                $candidateId = $candidate['candidate_id'];
                unset($candidate['candidate_id']);
                $candidatesEvaluations[$candidateId][] = $candidate;
            }
        }

        return $candidatesEvaluations;
    }

}