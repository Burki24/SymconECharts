<?php

declare(strict_types=1);
class EChartsWidget extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RequireParent('{33C9DF44-6F6D-5916-4AAE-CCB24BD6928D}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function Send()
    {
        $this->SendDataToParent(json_encode(['DataID' => '{4CB9F933-7B16-CC7E-D7C4-572C811AC8CC}']));
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
    }
}
