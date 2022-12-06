<?php
class Auth extends Trongate
{

    function login_from_app()
    {
        $data = $this->_get_data_from_app();
        $username  = $data['username'];
        $password = $data['password'];
        if ($username == 'quangquoc' && $password == '12345678') {
            $result = [
                'id' => '1',
                'name' => 'quangquoc',
                'username' => 'quangquoc',
                'email' => 'hunghungkg2006@yahoo.com',
                'role' => 'admin',
                'trongateToken' => 'kalsdjfklahjklasjhdlfkjaklwjlk'
            ];
            http_response_code(200);
            exit(json_encode($result));
        } else {
            http_response_code(400);
            exit();
        }
    }

    function _get_data_from_app()
    {
        $posted_data = file_get_contents('php://input');
        $data = (array) json_decode($posted_data);
        return $data;
    }
}
