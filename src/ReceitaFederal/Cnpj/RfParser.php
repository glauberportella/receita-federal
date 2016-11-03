<?php
namespace ReceitaFederal\Cnpj;

use ReceitaFederal\Cnpj\RfCaptcha;
use ReceitaFederal\Curl\RfCurl;

class RfParser
{
	private $rfcaptcha;
	private $inputCaptcha;
	private $inputCnpj;
	private $html;

	public function __construct(RfCaptcha $rfcaptcha, $inputCnpj, $inputCaptcha)
	{
		// limpa cnpj
		$inputCnpj = str_replace('/', '', str_replace('-', '', str_replace('.', '', $inputCnpj)));

		$this->rfcaptcha = $rfcaptcha;
		$this->inputCnpj = $inputCnpj;
		$this->inputCaptcha = $inputCaptcha;
		$this->html = $this->getHtmlCnpj();
		//file_put_contents('rfparser.log', $this->html."\n\n", FILE_APPEND);
	}

	/* parseHtmlCNPJ
	 * vai analisar/parsear o html e retorna os campos
	 * caso consiga extrair-lo com sucesso. Observem
	 * que usei a classe DomDocument presente na própria LP
	 * pois apesar da simplicidade que a biblioteca simple html dom parser
	 * me proporciona, ela é meio pesadinha e também
	 * afim de inseri-lo neste mundo de bot, queria lhe
	 * mostrar outras possibilidades para fazer a mesma coisa
	 *
	 *
	 * @param string $html
	 * @return array
	 */
	public function parse()
	{             
		$dom = new \DomDocument();
		@$dom->loadHTML($this->html);
		$q = $dom->getElementsByTagName('font');
		$len = $q->length;
		$campos = array();

		for($i = 4; $i < $len; $i++)
		{
			if(!isset($q->item(($i+1))->nodeValue))
				break;

			$current = trim($q->item($i)->nodeValue);
			$prox = trim($q->item(($i+1))->nodeValue);

			if($current == 'NÚMERO DE INSCRIÇÃO')
				$campos['numero_inscricao'] = preg_replace('/[a-zA-Z]+/i', '', $prox);                            

			if($current == 'DATA DE ABERTURA')
				$campos['data_abertura'] = $prox;                  

			if($current == 'NOME EMPRESARIAL')
				$campos['nome_empresarial'] = $prox;                           

			if($current == 'TÍTULO DO ESTABELECIMENTO (NOME DE FANTASIA)')
				$campos['titulo_estabelecimento'] = $prox;

			if($current == 'CÓDIGO E DESCRIÇÃO DA ATIVIDADE ECONÔMICA PRINCIPAL')
			{
				//while(strcasecmp($prox, 'código e descrição das atividades econômicas secundárias'))
				/*
				while($prox != 'código e descrição das atividades econômicas secundárias')
				{
					$campos['codDescAtivEconPrin'][] = preg_replace('/[ ]{2,}/', '', $prox);
					$i++;
					$prox = strtolower(trim(utf8_decode($q->item(($i+1))->nodeValue)));
				}
				*/
				$campos['descricao_cnae_principal'] = $prox;
				if (preg_match('/\d+\.\d+\-\d+\-\d+/', $prox, $matches)) {
					$campos['cnae'] = str_replace('.', '', str_replace('-', '', $matches[0]));
				}
			}

			if($current == 'CÓDIGO E DESCRIÇÃO DAS ATIVIDADES ECONÔMICAS SECUNDÁRIAS')
			{
				//while(strcasecmp($prox, 'código e descrição da natureza jurídica'))
				/*
				while($prox != 'código e descrição da natureza jurídica')
				{
					$campos['codDescAtivEconSec'][] = preg_replace('/[ ]{2,}/', '', $prox);
					$i++;
					$prox = strtolower(trim(utf8_decode($q->item(($i+1))->nodeValue)));
				}
				*/                          
				$campos['descricao_cnae_secundaria'] = $prox;
			}

			if($current == 'CÓDIGO E DESCRIÇÃO DA NATUREZA JURÍDICA')
				$campos['codigo_descricao_natureza_juridica'] = $prox;                                      
			if($current == 'LOGRADOURO')
				$campos['logradouro'] = $prox;                              

			if($current == 'NÚMERO')
				$campos['numero'] = is_numeric($prox) ? $prox : 0;                                                                                  
			if($current == 'COMPLEMENTO')
				$campos['complemento'] = $prox;        

			if($current == 'CEP')
				$campos['cep'] = preg_replace('#[^0-9]+#', '', $prox);

			if($current == 'BAIRRO/DISTRITO')
				$campos['bairro'] = $prox;

			if($current == 'MUNICÍPIO')
				$campos['municipio'] = $prox;

			if($current == 'UF')
				$campos['uf'] = $prox;

			if($current == 'SITUAÇÃO CADASTRAL')
				$campos['situacao_cadastral'] = $prox;

			if($current == 'DATA DA SITUAÇÃO CADASTRAL')
				$campos['data_situacao_cadastral'] = $prox;                                              
			if($current == 'MOTIVO DE SITUAÇÃO CADASTRAL')
				$campos['motivo_situacao_cadastral'] = $prox;
		}
		
		return $campos;
	}

	protected function getHtmlCnpj()
	{
        // aqui é aquele arquivo onde salvei os cookies lá em getCaptchaToken()
        if(!file_exists($this->rfcaptcha->getCookieFile())) {
			return false;
		}
		
        // aqui seto os campos que vou efetuar post pro server da RF
		$post = array(
			'origem' => 'comprovante',
			'search_type' => 'cnpj',
			'cnpj' => $this->inputCnpj,
			'txtTexto_captcha_serpro_gov_br' => $this->inputCaptcha,
			'captchaAudio' => '',
			'submit1' => 'Consultar',
			//'viewstate' => $this->rfcaptcha->getToken()
		);
		$post = http_build_query($post, NULL, '&');
        // tenho que enviar esse cookie pra eles ?
		$cookie = array('flag' => 1);
		$ch = curl_init(RfCaptcha::RFCAPTCHA_VALIDA_URL);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->rfcaptcha->getCookieFile());
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->rfcaptcha->getCookieFile());
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:8.0) Gecko/20100101 Firefox/8.0');
		curl_setopt($ch, CURLOPT_COOKIE, http_build_query($cookie, NULL, '&'));
		curl_setopt($ch, CURLOPT_REFERER, RfCaptcha::RFCAPTCHA_REQUEST_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$maxredirect = 3;
		if (ini_get('open_basedir') == '') {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $maxredirect);
			$html = curl_exec($ch);
		} else {
			$html = RfCurl::curlExecFollow($ch, $maxredirect);
		}

		curl_close($ch);

		return $html;
	}
}