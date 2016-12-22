<?php

namespace App\Service;

use GuzzleHttp\Client;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Egor Zyuskin <e.zyuskin@mildberry.com>
 */
class UtmClient extends Client
{
    /**
     * @var array
     */
    private $config;

    /**
     * UtmClient constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct(['http_errors' => false]);

        $this->config = $config;
    }

    /**
     * @param string $doc
     * @param string $utm
     * @return string
     */
    public function resendDoc(string $doc, string $utm)
    {
        if (empty($this->config['utm'][$utm])) {
            throw new BadRequestHttpException(sprintf('Настройки для УТМ: %s не найдены', $utm));
        }

        $utm = $this->config['utm'][$utm];
        $return = [];

        $ttnList = [];
        foreach (explode(',', $doc) as $ttn) {
            $ttn = (strtoupper(substr($ttn, 0, 3)) == 'TTN') ? trim(strtoupper($ttn)) : 'TTN-'.trim($ttn);
            if (strlen($ttn) == 14) {
                $ttnList[] = $ttn;
            } else {
                $return[$ttn] = 'Не верный формат ТТН';
            }
        }

        if (!empty($ttnList)) {
            foreach ($ttnList as $ttn) {
                $return[$ttn] = $this->sendFile($this->createRequestFile($ttn, $utm['id']), $utm['host']);
            }
        }
        return $return;
    }

    /**
     * @param $fileName
     * @param $host
     * @return string
     */
    private function sendFile($fileName, $host)
    {
        $response = $this->request('POST', 'http://'.$host.':8080/opt/in/QueryResendDoc', [
            'multipart' => [
                [
                    'name' => 'xml_file',
                    'contents' => fopen($fileName, 'r'),
                ]
            ],
        ]);

        unlink($fileName);

        if ($response->getStatusCode() != 200) {
            return 'Ошибка запроса ТТН Error: '.$response->getStatusCode();
        }

        return $response->getBody()->getContents();
    }

    /**
     * @param string $ttn
     * @param string $uid
     * @return string
     */
    private function createRequestFile(string $ttn, string $uid)
    {
        $fileName = $this->getUniqueFileName();

        $content = '<?xml version="1.0" encoding="UTF-8"?><ns:Documents Version="1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ns="http://fsrar.ru/WEGAIS/WB_DOC_SINGLE_01" xmlns:qp="http://fsrar.ru/WEGAIS/QueryParameters">
<ns:Owner>
<ns:FSRAR_ID>'.$uid.'</ns:FSRAR_ID> </ns:Owner>
<ns:Document>
<ns:QueryResendDoc> <qp:Parameters>
<qp:Parameter> <qp:Name>WBREGID</qp:Name>
<qp:Value>'.$ttn.'</qp:Value>
</qp:Parameter></qp:Parameters>
</ns:QueryResendDoc> </ns:Document>
</ns:Documents>';

        file_put_contents($fileName, $content);

        return $fileName;
    }

    /**
     * @return string
     */
    private function getUniqueFileName()
    {
        $name = sys_get_temp_dir().'QueryResendDoc'.rand(1, 999999).'.xml';

        while (file_exists(sys_get_temp_dir().'/'.$name)) {
            $name = sys_get_temp_dir().'QueryResendDoc'.rand(1, 999999).'.xml';
        }

        return  $name;
    }
}
