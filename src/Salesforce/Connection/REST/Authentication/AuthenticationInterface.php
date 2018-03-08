<?php

namespace Stratease\Salesforcery\Salesforce\Connection\REST\Authentication;

interface AuthenticationInterface
{
    public function getAccessToken();

    public function getInstanceUrl();

    public function authenticate();
}
