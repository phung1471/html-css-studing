<?php

class GatewayMessage extends PaymentMessage
{

    private static $db = array(
        "Gateway" => "Varchar",
        "Reference" => "Varchar(255)", //remote id
        "Code" => "Varchar"
    );

    private static $summary_fields = array(
        'Type',
        'Reference',
        'Message',
        'Code'
    );
}
