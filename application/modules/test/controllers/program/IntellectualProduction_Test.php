<?php

/**
 ***** IntellectualProduction class(on /program/domain/intellectualproduction) test class.
 *
 *
 * Provide unit tests for the IntellectualProduction class hierarchy methods.
 * To access the report generated by these tests, type on the URL: '../intellectual_production_test'
 */


require_once(MODULESPATH."/program/exception/IntellectualProductionException.php");
require_once(MODULESPATH."/program/domain/intellectual_production/Intellectualproduction.php");
require_once(MODULESPATH."/auth/domain/User.php");
require_once 'TestCase.php';

class IntellectualProduction_Test extends TestCase{

    public function __construct(){
        parent::__construct($this);

        $user = new User($id = 1, $name = "John");

        $this->author = 1;
        $this->title = "Diga aonde você vai!";
        $this->type = 0; // Bibliographic
        $this->year = "2016";
        $this->subtype = 1; // Journal Article
        $this->qualis = "B1";
        $this->periodic = "Molejo";
        $this->identifier = "0124-5678"; // ISSN
    }


    public function shouldCreateIntellectualProductionWithAllData(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, $this->year, 
                                                    $this->subtype, $this->qualis, $this->periodic, $this->identifier);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if instantiate with all data";

        $this->unit->run($this->author, $production->getAuthor(), $test_name, $notes);

    }

    public function shouldCreateIntellectualProductionWithAuthorAndTitle(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if instantiate with author and title";

        $this->unit->run($this->author, $production->getAuthor(), $test_name, $notes);
    }

    public function shouldNotCreateIntellectualProductionWithBlankTitle(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, "");
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with blank title";

        $this->unit->run($production, "is_false", $test_name, $notes);
    }


    public function shouldNotCreateIntellectualProductionWithNullTitle(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, NULL);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with null title";

        $this->unit->run($production, "is_false", $test_name, $notes);
    }

    public function shouldNotCreateIntellectualProductionWithInvalidYear5Digits(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, "20016", 
                                                    $this->subtype, $this->qualis, $this->periodic, $this->identifier);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with year with 5 digits";

        $this->unit->run($production, "is_false", $test_name, $notes);

    }


    public function shouldNotCreateIntellectualProductionWithInvalidYear3Digits(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, "216", 
                                                    $this->subtype, $this->qualis, $this->periodic, $this->identifier);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with year with 3 digits";

        $this->unit->run($production, "is_false", $test_name, $notes);

    }


    public function shouldNotCreateIntellectualProductionWithInvalidType(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, 65, $this->year, 
                                                    $this->subtype, $this->qualis, $this->periodic, $this->identifier);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with invalid type";

        $this->unit->run($production, "is_false", $test_name, $notes);
    }

    public function shouldNotCreateIntellectualProductionWithInvalidSubtype(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, $this->year, 
                                                    65, $this->qualis, $this->periodic, $this->identifier);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with invalid subtype";

        $this->unit->run($production, "is_false", $test_name, $notes);
    }

    public function shouldNotCreateIntellectualProductionWithInvalidQualis(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, $this->year, 
                                                    $this->subtype, "B23", $this->periodic, $this->identifier);
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with invalid qualis";

        $this->unit->run($production, "is_false", $test_name, $notes);
    }

    public function shouldCreateIntellectualProductionWithIdentifierISBN(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, $this->year, 
                                                    $this->subtype, $this->qualis, $this->periodic, "1234567891234");
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if instantiate with isbn identifier";
        $this->unit->run("1234567891234", $production->getIdentifier(), $test_name, $notes);
    }

    public function shouldNotCreateIntellectualProductionWithInvalidIdentifier(){

        $notes = "";
        try{
            $production = new IntellectualProduction($this->author, $this->title, $this->type, $this->year, 
                                                    $this->subtype, $this->qualis, $this->periodic, "12345");
        
        }
        catch (IntellectualProductionException $exception){
            $production = FALSE;
            $notes = "<b>Thrown Exception:</b> <i>".get_class($exception)."</i> - ".$exception->getMessage();
        }

        $test_name = "Test if not instantiate with invalid identifier";

        $this->unit->run($production, "is_false", $test_name, $notes);
    }

}
