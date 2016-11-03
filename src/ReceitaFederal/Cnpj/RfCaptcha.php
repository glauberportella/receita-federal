<?php

namespace ReceitaFederal\Cnpj;

use ReceitaFederal\Exception\RfCaptchaRequestException;
use ReceitaFederal\Exception\RfCaptchaContentTypeException;
use ReceitaFederal\Exception\RfCaptchaImageException;
use Zend\Dom\Query;

class RfCaptcha implements \Serializable
{
	const RFCAPTCHA_BASE = 'http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva';
	const RFCAPTCHA_REQUEST_URL = 'http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva/Cnpjreva_Solicitacao2.asp?cnpj=';
	const RFCAPTCHA_URL = 'http://www.receita.fazenda.gov.br/scripts/captcha/Telerik.Web.UI.WebResource.axd?type=rca&guid=';
	const RFCAPTCHA_VALIDA_URL = 'http://www.receita.fazenda.gov.br/pessoajuridica/cnpj/cnpjreva/valida.asp';

	protected $cookiePath;
	protected $cookieFile;
	protected $idCaptcha;
	protected $token;

	private $captchaImgUrl;

	public function __construct($cookiePath = '')
	{
		$this->cookiePath = $cookiePath;
		if (!empty($cookiePath))
			$this->cookieFile = $cookiePath.DIRECTORY_SEPARATOR.session_id();
		else
			$this->cookieFile = session_id();

	    if(!file_exists($this->cookieFile))
	    {
			$file = fopen($this->cookieFile, 'w');
			fclose($file);
			chmod($this->cookieFile, 0777);
		}
	    $ch = curl_init(self::RFCAPTCHA_REQUEST_URL);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
	    $html = curl_exec($ch);
	    curl_close($ch);

		//bem fiz a requisição via curl e vou assumir que ocorreu tudo ok, ou seja obtive um http code 200 e o html veio pra mim bonito.
	    if(!$html)
			throw new RfCaptchaRequestException('Ocorreu erro ao obter html de "'.self::RFCAPTCHA_URL.'".');

		// carrego o html na biblioteca
	    //$html = new \Simple_html_dom($html);
	    $dom = new Query($html);
	    
	    // variáveis que vão guardar a url do captcha e o token
		$url_imagem = $tokenValue = '';

	    // pra quem está acostumado com jquery vai achar isso bem familiar, vou pegar a imagem que possuir o id imgcaptcha
		$img = $dom->execute('#imgCaptcha');
		// verifico se pegou alguma coisa
		if(count($img))
	    {
			// percorro o laço para conseguir extrair a informação que quero, nesse caso a url da imagem
			$img = $img->current();
			$attribs = $img->attributes;
			foreach($attribs as $imgAttr) {
				if ($imgAttr->name === 'src') {
					$url_imagem = $imgAttr->value;
					break;
				}
			}

			if ($url_imagem) {
				$url_imagem = self::RFCAPTCHA_BASE . '/' . preg_replace('#^\./#i', '', $url_imagem);
			}

			$this->captchaImgUrl = $url_imagem;

			/*
			// essa er eh pra pegar somente o id do captcha
			if(preg_match('#guid=(.*)$#', $url_imagem, $arr))
			{
				$idCaptcha = $arr[1];
				// aqui é onde eu pego o token da página
				//$viewstate = $html->find('input[id=viewstate]');
				$viewstate = $dom->execute('#viewstate');
				if(count($viewstate))
				{
					$viewstate = $viewstate->current();
					$attribs = $viewstate->attributes;
					foreach($attribs as $inputViewstate) {
						if ($inputViewstate->name === 'value') {
							$tokenValue = $inputViewstate->value;
							break;
						}
					}
				}                                            
				// caso tenha pego $idCaptcha e $tokenValue eu retorno eles num array
				if(!empty($idCaptcha) && !empty($tokenValue)) {
					$this->idCaptcha = $idCaptcha;
					$this->token = $tokenValue;
				}
			}
			*/
		}		
	}

	public function serialize()
	{
        return serialize(array(
			'cookiePath' => $this->cookiePath,
			'cookieFile' => $this->cookieFile,
			'idCaptcha' => $this->idCaptcha,
			'token' => $this->token
		));
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        $this->setCookiePath($data['cookiePath']);
        $this->setCookieFile($data['cookieFile']);
        $this->setIdCaptcha($data['idCaptcha']);
        $this->setToken($data['token']);
    }

	public function setCookiePath($cookiePath)
	{
		$this->cookiePath = $cookiePath;
		return $this;
	}

	public function getCookiePath()
	{
		return $this->cookiePath;
	}

	public function setCookieFile($cookieFile)
	{
		$this->cookieFile = $cookieFile;
		return $this;
	}

	public function getCookieFile()
	{
		return $this->cookieFile;
	}

	public function setIdCaptcha($idCaptcha)
	{
		$this->idCaptcha = $idCaptcha;
		return $this;
	}

	public function getIdCaptcha()
	{
		return $this->idCaptcha;
	}

	public function setToken($token)
	{
		$this->token = $token;
		return $this;
	}

	public function getToken()
	{
		return $this->token;
	}

	public function getCaptcha()
	{
		if (!$this->captchaImgUrl)
			return null;

		// tmp file for mime verification
		$tmpFilename = $this->cookiePath.DIRECTORY_SEPARATOR.uniqid();
		$fp = fopen($tmpFilename, 'wb');
		if (!$fp)
			return null;
		if (!fwrite($fp, file_get_contents($this->captchaImgUrl)))
			return null;
		$mime = mime_content_type($tmpFilename);
		if (!$mime)
			return null;
		fclose($fp);
		unlink($tmpFilename);

		switch ($mime) {
			case 'image/png':
				$img = imagecreatefrompng($this->captchaImgUrl);
				break;
			case 'image/jpeg':
				$img = imagecreatefromjpeg($this->captchaImgUrl);
				break;
			case 'image/gif':
				$img = imagecreatefromgif($this->captchaImgUrl);
				break;
			default:
				return null;
		}

		$imgfile = $this->cookiePath.DIRECTORY_SEPARATOR.'receita.jpg';

		if (false === imagejpeg($img, $imgfile))
        	throw new RfCaptchaImageException('Ocorreu erro ao salvar imagem do captcha no servidor. Caminho "'.$this->cookiePath.'".');

        return basename($imgfile);

		/*
		if(preg_match('#^[a-z0-9-]{36}$#', $this->idCaptcha))
		{
		    $url = self::RFCAPTCHA_URL.$this->idCaptcha;

		    //  poderiamos fazer simplemente
		    // * $imgsource = file_get_contents($url);
		    // * mas, para evitar possíveis problemas com allow_url_fopen
		    // * vamos usar somente curl pra garantir
		    
		    $ch = curl_init($url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
		    $imgsource = curl_exec($ch);
		    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		    curl_close($ch);
		    //  se tiver obtido sucesso em pegar a imagem
		    // * crio uma imagem a partir da string usando imagecreatefromstring
		    // * e seto o header para image/jpg e mando
		    // * o browser exibir ela.
		    // * poderia usar curl_getinfo($ch) para analisar
		    // * CONTENT_TYPE retornado pelo servidor, pra garantir
		    // * que é uma imagem e o formato é jpg, pois caso
		    // * o id tenha expirado o server retorna um gif, então
		    // * deixo isso como exercício.
		    
	    	if (!in_array($content_type, array('image/jpg', 'image/jpeg')))
	    		throw new RfCaptchaContentTypeException('ContentType inválido, esperado image/jpeg obteve '.$content_type.'. Possivelmente o ID de sessão expirou na requisição à Receita Federal.');

		    if(!empty($imgsource))
		    {
		        $img = imagecreatefromstring($imgsource);
		        $imgfile = $this->cookiePath.DIRECTORY_SEPARATOR.'receita.jpg';
		        
		        //if (file_exists($imgfile))
		        //	unlink($imgfile);

		        if (false === imagejpeg($img, $imgfile))
		        	throw new RfCaptchaImageException('Ocorreu erro ao salvar imagem do captcha no servidor. Caminho "'.$this->cookiePath.'".');

		        //chmod($imgfile, 0755);

		        return basename($imgfile);
		    }
		}

		return null;
		*/

	}
}