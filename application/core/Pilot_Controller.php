<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . '../tools/multitenant/PilotAccessGate.php';

class Pilot_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!PilotAccessGate::isAllowed(ENVIRONMENT)) {
            show_404();
        }
    }
}
