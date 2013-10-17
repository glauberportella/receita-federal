receita-federal
===============

Consultas na Receita Federal, atualmente somente consulta CNPJ.

Uso
---

A biblioteca não burla o sistema de captcha da Receita, o primeiro passo é obter
o captcha e exibí-lo ao usuário e solicitar que o usuário digite os caracteres.

```php
// $cookieFile deve ser o caminho onde será salvo cookie da receita federal
$rfCaptcha = new \ReceitaFederal\Cnpj\RfCaptcha($cookieFilePath);

// obtem imagem e token viewstate da receita
$captchaImg = $rfCaptcha->getCaptcha();
$viewstate = $rfCaptcha->getToken();
```

Após obter a imagem e o token viewstate rodamos o parser
```php
// $cnpj e $captcha sao os inputs informados pelo usuário
// $cnpj pode ter pontuação ou não
// $captcha deve conter os caracteres presentes na imagem de captcha obtida
$rfParser = new \ReceitaFederal\Cnpj\RfParser($rfCaptcha, $cnpj, $captcha);
// o parser retorna array com os dados obtidos do cartão do cnpj
$dadosCnpj = $rfParser->parse();
```

Retorno de RfParser::parser()
------------------------------

```php
// array retornado por \ReceitaFederal\Cnpj\RfParser::parse()
array(
	'numero_inscricao',						// NÚMERO DE INSCRIÇÃO
	'data_abertura',						// DATA DE ABERTURA
	'nome_empresarial',						// NOME EMPRESARIAL
	'titulo_estabelecimento', 				// TÍTULO DO ESTABELECIMENTO (NOME DE FANTASIA)
	'descricao_cnae_principal', 			// CÓDIGO E DESCRIÇÃO DA ATIVIDADE ECONÔMICA PRINCIPAL
	'descricao_cnae_secundaria',			// CÓDIGO E DESCRIÇÃO DAS ATIVIDADES ECONÔMICAS SECUNDÁRIAS
	'codigo_descricao_natureza_juridica',	// CÓDIGO E DESCRIÇÃO DA NATUREZA JURÍDICA
	'logradouro',							// LOGRADOURO
	'numero',								// NÚMERO
	'complemento',							// COMPLEMENTO
	'cep',									// CEP
	'bairro',								// BAIRRO/DISTRITO
	'municipio',							// MUNICÍPIO
	'uf',									// UF
	'situacao_cadastral', 					// SITUAÇÃO CADASTRAL
	'data_situacao_cadastral',				// DATA DA SITUAÇÃO CADASTRAL
	'motivo_situacao_cadastral',			// MOTIVO DA SITUAÇÃO CADASTRAL
);
```

Problemas percebidos
---------------------

Um problema percebido é quanto a configuração do PHP quando se usa open_basedir.

Quando open_basedir está configurada no PHP o captcha e token são invalidados
no servidor da Receita (motivo não entendido ainda), em \ReceitaFederal\Curl\RfCurl
existe uma implmenetação para lidar com problema de open_basedir e CURLOPT_FOLLOWLOCATION, 
porém não resolveu para a consulta na Receita Federal, qualquer ajuda em solucionar
esse problema é bem vinda.

Requisitos
-----------

- PHP 5.3.3 >=
- Zend_Dom
- cURL