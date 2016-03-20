<!--Set the information to the tabs-->
<?php 
	if($isFirst){
		echo "<div class='tab-pane fade in active' id='".$tabId."'>";
	}
	else{
		echo "<div class='tab-pane fade' id='".$tabId."'>";
	}


	if($tabId != "program".MAX_QUANTITY_OF_TABS){

?>
    <a class="nav-tabs-dropdown btn btn-block btn-primary"><h3>Sobre o <?php echo $program['program_name']
	?></h3></a>

	<div class="box-body">
      <div class="box-group" id="accordion">
            <!-- we are adding the .panel class so bootstrap.js collapse plugin detects it -->
            <div class="panel box box-primary">
              <div class="box-header with-border">
                <h4 class="box-title">
                  <a data-toggle="collapse" data-parent="#accordion" href=<?="#summary".$program['id_program']?> aria-expanded="false">
					O <?php	echo $program['acronym']?>
                  </a>
                </h4>
              </div>
              <div id=<?="summary".$program['id_program']?> class="panel-collapse collapse" aria-expanded="false">
                <div class="box-body">			

				<?php $programSummary = $program['summary'];

					if (!empty($programSummary)) {?>

							<p><?php echo $programSummary?></p>
						
				<?php
				} ?>

                </div>
              </div>
            </div>


	
<?php		$programHistory = $program['history'];

			if (!empty($programHistory)) {?>
	
            <div class="panel box box-primary">
              <div class="box-header with-border">
                <h4 class="box-title">
                  <a data-toggle="collapse" data-parent="#accordion" href=<?="#history".$program['id_program']?> aria-expanded="false">
					Histórico
                  </a>
                </h4>
              </div>
              	<div id=<?="history".$program['id_program']?> class="panel-collapse collapse" aria-expanded="false">
                <div class="box-body">			
					<p><?php echo $programHistory?></p>
					</div>
				</div>
			</div>

		<?php
			}

		$programContact = $program['contact'];

			if (!empty($programContact)) {?>
	
				<div class="panel box box-primary">
              		<div class="box-header with-border">
	                <h4 class="box-title">
	                  <a data-toggle="collapse" data-parent="#accordion" href=<?="#contact".$program['id_program']?> aria-expanded="false" class="collapsed">
						Contato
	                  </a>
	                </h4>
	              </div>
	              	<div id=<?="contact".$program['id_program']?> class="panel-collapse collapse" aria-expanded="false">
	                	<div class="box-body">			

						<p><?php echo $program['contact']?></p>
						</div>
					</div>
				</div>
		
		<?php
			}

			if ($coursesPrograms !== FALSE) {
			
				$coursesProgram = $coursesPrograms[$program['id_program']];
				$researchLines = $coursesProgram['researchLines']; 
				
				if(!empty($researchLines)){ ?>

					<div class="panel box box-primary">
		              <div class="box-header with-border">
		                <h4 class="box-title">
		                  <a data-toggle="collapse" data-parent="#accordion" href=<?="#research".$program['id_program']?> aria-expanded="false" >
							Linhas de Pesquisa
		                  </a>
		                </h4>
		              </div>
		              <div id=<?="research".$program['id_program']?> class="panel-collapse collapse" aria-expanded="false" >
		                <div class="box-body">			
						<?php
							foreach ($researchLines as $researchLine) {

								echo "<p>{$researchLine}</p>";							
							}
						?> 

						</div>
					 </div>
					</div>	

				<?php 
				}

			} ?>




		</ul>
	   	<?php include ('_courses_information.php'); ?>
        </div>
    </div>
	<?php }
		else { 
			include("_other_programs.php"); 

		} ?>
</div>
	