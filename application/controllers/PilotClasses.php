<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PilotClasses extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database('school_saas_pilot');
        require_once APPPATH . 'core/Tenant_Model.php';
        $this->load->model('Tenant_Model', 'tenant_model');
    }

    public function index()
    {
        $classes = $this->tenant_model->tenantGetAll('classes');
        $sections = $this->tenant_model->tenantGetAll('sections');
        $classSections = $this->tenant_model->tenantGetAll('class_sections');

        $sectionsById = [];
        foreach ($sections as $section) {
            $sectionsById[$section['id']] = $section['section'];
        }

        $classSectionsByClassId = [];
        foreach ($classSections as $link) {
            $classSectionsByClassId[$link['class_id']][] = $sectionsById[$link['section_id']] ?? 'Unknown';
        }

        $rows = [];
        foreach ($classes as $class) {
            $rows[] = [
                'class' => $class['class'],
                'sections' => $classSectionsByClassId[$class['id']] ?? [],
            ];
        }

        $this->load->view('pilot_classes', ['rows' => $rows]);
    }
}
