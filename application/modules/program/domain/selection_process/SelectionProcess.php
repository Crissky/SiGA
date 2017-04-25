<?php

require_once APPPATH."/exception/SelectionProcessException.php";
require_once "ProcessSettings.php";

abstract class SelectionProcess{
	
	const INVALID_NAME = "O nome do edital não pode estar em branco.";
	const INVALID_COURSE = "Um processo seletivo deve estar vinculado à algum curso de um programa.";
	const INVALID_ID = "O ID do processo seletivo deve ser um número maior que zero.";
	const INVALID_SETTINGS = "Configurações inválidas para o processo seletivo.";
	const INVALID_NOTICE_PATH = "O caminho para o edital informado é inválido ou não existe.";

	const INVALID_VACANCIES = "O número de vagas deve ser maior que zero.";
	const VACANCIES_REQUIRED = "O número de vagas é de preenchimento obrigatório.";

	const MIN_VACANCIES = 1;

	const MIN_ID = 1;

	private $id;
	private $name;
	private $vacancies;

	// Foreign Key from Course. Course id
	private $course;

	private $noticePath;
	protected $settings;

	public function __construct($course = FALSE, $name = "", $id = FALSE, $vacancies){
		$this->setCourse($course);
		$this->setName($name);
		$this->setId($id);
		$this->setVacancies($vacancies);
	}

	public function addSettings($settings){
		if(is_object($settings) && get_class($settings) == "ProcessSettings" && !is_null($settings)){
			$this->settings = $settings;
		}else{
			throw new SelectionProcessException(self::INVALID_SETTINGS);
		}
	}

	public function setNoticePath($path){
		
		if(is_string($path)){
			if(file_exists($path)){
				$this->noticePath = $path;
			}else{
				throw new SelectionProcessException(self::INVALID_NOTICE_PATH);
			}
		}else{
			throw new SelectionProcessException(self::INVALID_NOTICE_PATH);
		}
	}

	private function setName($name){
		
		if(!empty($name)){

			$this->name = $name;
		}else{
			throw new SelectionProcessException(self::INVALID_NAME);
		}
	}

	private function setCourse($course){

		if($course !== FALSE){
			if(!is_nan((double) $course) && $course > 0){
				$this->course = $course;
			}else{
				throw new SelectionProcessException(self::INVALID_COURSE);
			}
		}else{
			throw new SelectionProcessException(self::INVALID_COURSE);
		}
	}

	private function setId($id){

		if($id !== FALSE){

			if(!is_nan((double) $id) && ctype_digit($id) && $id > 0){
				$this->id = $id;
			}else{
				throw new SelectionProcessException(self::INVALID_ID);
			}
		}else{
			//If the ID is FALSE, is because is a new object, not coming from DB
			$this->id = $id;
		}
	}

	private function setVacancies($vacancies){
		if($vacancies != ''){
            if($vacancies >= self::MIN_VACANCIES){
                $this->vacancies = $vacancies;
            }
            else{
                throw new SelectionProcessException(self::INVALID_VACANCIES);
            }
        }
        else{
            throw new SelectionProcessException(self::VACANCIES_REQUIRED);
        }
	}

	public function getName(){
		return $this->name;
	}

	public function getCourse(){
		return $this->course;
	}

	public function getId(){
		return $this->id;
	}

	public function getSettings(){
		return $this->settings;
	}

	public function getNoticePath(){
		return $this->noticePath;
	}

	public function getVacancies(){
		return $this->vacancies;
	}

	public abstract function getType();
	public abstract function getFormmatedType();
}