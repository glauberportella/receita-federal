<!DOCTYPE html>
<html lang="pt-br">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Demo glauberportella/receita-federal</title>

		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->

		<style type="text/css">
			body {
				padding-top: 50px;
			}
		</style>
	</head>
	<body>
		<div class="container">
			
			<?php
			require_once dirname(__FILE__).'/../vendor/autoload.php';

			$captcha = new \ReceitaFederal\Cnpj\RfCaptcha(dirname(__FILE__));

			$dados = null;

			if (isset($_POST['enviar'])) {
				$parser = new \ReceitaFederal\Cnpj\RfParser($captcha, $_POST['cnpj'], $_POST['captcha']);
				$dados = $parser->parse();
			}
			?>


			<form method="post">
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label for="cnpj">CNPJ *</label>
							<input type="text" name="cnpj" class="form-control" required>
						</div>
						<div class="form-group">
							<img src="<?php echo $captcha->getCaptcha(); ?>" alt=""><br>
							<label for="captcha">Digite os caracteres acima *</label>
							<input type="text" name="captcha" class="form-control" required>
						</div>
					</div>
					<div class="col-md-8">
						<?php if ($dados): ?>
							<div class="alert alert-info">
								<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
								<strong>Retorno</strong><br>
								<table class="table-bordered">	
								<?php foreach ($dados as $key => $value): ?>
									<tr>
										<th><?php echo $key ?></th>
										<td><?php echo $value ?></td>
									</tr>
								<?php endforeach; ?> 
								</table>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<p>
					<button type="submit" name="enviar" class="btn btn-success btn-lg">Enviar</button>
				</p>
			</form>

		</div>

		<!-- jQuery -->
		<script src="//code.jquery.com/jquery.js"></script>
		<!-- Bootstrap JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
	</body>
</html>