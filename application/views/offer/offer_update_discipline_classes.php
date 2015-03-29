
<br>

<?php 
	
	if($disciplineData !== FALSE){
		if($offerDisciplineData !== FALSE){
			formToUpdateOfferDisciplineClass($disciplineData['discipline_code'], $idOffer, $teachers, $offerDisciplineData);
		}else{
			$status = "danger";
			$message = "Não foi possível recuperar os dados desta turma. Tente novamente.";
			$this->session->set_flashdata($status, $message);
			redirect("offer/displayDisciplineClasses/{$disciplineData['discipline_code']}/{$idOffer}");
		}

		echo anchor(
			"offer/displayDisciplineClasses/{$disciplineData['discipline_code']}/{$idOffer}",
			"Voltar",
			"class='btn btn-danger'"
		);
	}else{
?>
	<div class="callout callout-danger">
		<h4>O código da disciplina informado não foi encontrado. Por favor contate o administrador.</h4>
	</div>
<?php
	}
?> 

