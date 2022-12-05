<?php
class Auth extends Trongate
{

    function login_from_app()
    {
        $posted_data = file_get_contents('php://input');
        $data = (array) json_decode($posted_data);
        $username  = $data['username'];
        $password = $data['password'];
        $this->module('trongate_tokens');
        $token = $this->trongate_tokens->_attempt_get_valid_token();
        if ($username == 'quangquoc' && $password == '12345678') {
            $result = [
                "status" => true,
                "user" => [
                    'id' => '1',
                    'name' => 'Golden',
                    'username' => 'quangquoc',
                    'email' => 'hunghungkg2006@yahoo.com',
                    'accessToken' => $token
                ]
            ];
            http_response_code(200);
            exit(json_encode($result));
        } else {
            http_response_code(400);
            exit();
        }
    }
}
