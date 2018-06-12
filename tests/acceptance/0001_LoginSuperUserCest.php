<?php

class LoginSuperUserCest
{
    public function _before(SuperUser $I)
    {
    }

    public function _after(SuperUser $I)
    {
    }

    public function test_install(SuperUser $i){
        $i->install();
    }

    public function test_wrongLogin(SuperUser $i){
        $i->amOnPage('/admin');
        $i->tryLogin('root','root');
        $i->waitForText('Incorrect');
        $i->see('Incorrect');
    }

    public function test_login(SuperUser $i){
        $i->amOnPage('/admin');
        $i->login('management@xavoc.com');
        $i->see('Dashboard');
    }

}
