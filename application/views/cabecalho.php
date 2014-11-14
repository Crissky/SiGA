<!DOCTYPE html>
<?php  $session = $this->session->userdata("usuario_logado"); ?>
<html>
<head>
	<meta charset="UTF-8">
	<title>SIP</title>
	<link rel="stylesheet" href=<?=base_url("css/bootstrap.css")?>>
	<link rel="stylesheet" href=<?=base_url("css/estilo.css")?>>
	<script src=<?=base_url("js/funcoes.js")?>></script>
</head>

<body>
<div class="navbar navbar-inverse navbar-fixed-top">
<div class="container">
<div class="navbar-header">
	<?=anchor("/", "Home", "class='navbar-brand'")?>
</div>
	<div>
	<ul class="nav navbar-nav">
			<li><?=anchor("cadastro", "Cadastrar")?></li>
	<?php if ($session) { ?>
			<li><?=anchor("funcionarios", "Funcionários")?></li>
			<li><?=anchor("setores", "Setores")?></li>
			<li><?=anchor("funcoes", "Funções")?></li>
			<li><?=anchor("departamentos", "Departamentos")?></li>
			<li><?=anchor("conta", "Conta")?></li>
			<li><?=anchor("logout", "Sair")?></li>
	</ul>
	<?php } ?>
	</div>
</div>
</div>
<div class="container">

<?php
if ($this->session->flashdata("success")) : ?>
	<p class="alert alert-success text-center"><?= $this->session->flashdata("success") ?></p>
<?php endif;
if ($this->session->flashdata("danger")) : ?>
	<p class="alert alert-danger text-center"><?= $this->session->flashdata("danger") ?></p>
<?php endif ?>
