<?php
$filters = isset($filters) ? $filters : array();
$students = isset($students) ? $students : array();
$field_options = isset($field_options) ? $field_options : array();
$campus_list = isset($campus_list) ? $campus_list : array();
$classlist = isset($classlist) ? $classlist : array();
$sectionlist = isset($sectionlist) ? $sectionlist : array();
$selected_fields = isset($filters['display_fields']) ? $filters['display_fields'] : array_keys($field_options);
$selected_gender = isset($filters['gender']) ? $filters['gender'] : array('male', 'female');
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-filter"></i> Filtered Student Details
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
                        <div class="box-tools pull-right">
                            <a href="<?php echo site_url('student/search'); ?>" class="btn btn-default btn-sm">
                                <i class="fa fa-arrow-left"></i> Back to Student Details
                            </a>
                        </div>
                    </div>
                    <form action="<?php echo site_url('student/filteredstudentdetails'); ?>" method="post">
                        <div class="box-body">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Campus</label>
                                        <select name="campus_id" id="campus_id" class="form-control">
                                            <?php foreach ($campus_list as $campus) { ?>
                                                <option value="<?php echo $campus['id']; ?>" <?php echo (isset($filters['campus_id']) && (string) $filters['campus_id'] === (string) $campus['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $campus['name']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('class'); ?></label>
                                        <select name="class_id" id="class_id" class="form-control">
                                            <option value="">All Class</option>
                                            <?php foreach ($classlist as $class) { ?>
                                                <option value="<?php echo $class['id']; ?>" <?php echo (isset($filters['class_id']) && (string) $filters['class_id'] === (string) $class['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $class['class']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('section'); ?></label>
                                        <select name="section_id" id="section_id" class="form-control">
                                            <option value="">All Section</option>
                                            <?php foreach ($sectionlist as $section) { ?>
                                                <option value="<?php echo $section['section_id']; ?>" <?php echo (isset($filters['section_id']) && (string) $filters['section_id'] === (string) $section['section_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $section['section']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label>Gender Type</label>
                                    <div class="checkbox">
                                        <label style="margin-right: 15px;">
                                            <input type="checkbox" name="gender[]" value="male" <?php echo in_array('male', $selected_gender, true) ? 'checked' : ''; ?>> Male
                                        </label>
                                        <label>
                                            <input type="checkbox" name="gender[]" value="female" <?php echo in_array('female', $selected_gender, true) ? 'checked' : ''; ?>> Female
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <label>Show Fields</label>
                            <div class="row">
                                <?php foreach ($field_options as $field_key => $field_label) { ?>
                                    <div class="col-md-3 col-sm-4 col-xs-6">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" class="display-field-checkbox" name="display_fields[]" value="<?php echo $field_key; ?>" <?php echo in_array($field_key, $selected_fields, true) ? 'checked' : ''; ?>>
                                                <?php echo $field_label; ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary btn-sm pull-right">
                                <i class="fa fa-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list"></i> Student List (<?php echo count($students); ?>)</h3>
                    </div>
                    <div class="box-body">
                        <?php if (!empty($students)) { ?>
                            <div class="table-responsive">
                                <div class="download_label">Filtered Student Details</div>
                                <table class="table table-striped table-bordered table-hover example">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Campus</th>
                                            <?php foreach ($field_options as $field_key => $field_label) { ?>
                                                <th class="field-col field-col-<?php echo $field_key; ?>"><?php echo $field_label; ?></th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $index => $student) { ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo $student['campus']; ?></td>
                                                <?php foreach ($field_options as $field_key => $field_label) { ?>
                                                    <td class="field-col field-col-<?php echo $field_key; ?>">
                                                        <?php
if (($field_key === 'admission_date' || $field_key === 'dob') && !empty($student[$field_key]) && $student[$field_key] !== '0000-00-00') {
    echo $this->customlib->dateformat($student[$field_key]);
} else {
    echo isset($student[$field_key]) ? $student[$field_key] : '';
}
?>
                                                    </td>
                                                <?php } ?>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-info">No student record found for selected filters.</div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        toggleCampusDependentFilters($('#campus_id').val());
        toggleVisibleColumns();
    });

    $(document).on('change', '#campus_id', function() {
        var campus_id = $(this).val();
        $('#class_id').html('<option value="">All Class</option>');
        $('#section_id').html('<option value="">All Section</option>');
        toggleCampusDependentFilters(campus_id);
    });

    $(document).on('change', '#class_id', function() {
        var campus_id = $('#campus_id').val();
        var class_id = $(this).val();
        $('#section_id').html('<option value="">All Section</option>');
        if (campus_id !== '' && class_id !== '') {
            getSectionsByCampus(campus_id, class_id);
        }
    });

    $(document).on('change', '.display-field-checkbox', function() {
        toggleVisibleColumns();
    });

    function toggleCampusDependentFilters(campus_id) {
        var disable_filters = (campus_id === '');
        $('#class_id').prop('disabled', disable_filters);
        $('#section_id').prop('disabled', disable_filters);
        if (!disable_filters) {
            getClassesByCampus(campus_id);
        }
    }

    function getClassesByCampus(campus_id) {
        $.ajax({
            type: "GET",
            url: baseurl + "student/getFilteredClassesByCampus",
            data: {campus_id: campus_id},
            dataType: "json",
            success: function(data) {
                var selected_class_id = '<?php echo isset($filters['class_id']) ? $filters['class_id'] : ''; ?>';
                var selected_section_id = '<?php echo isset($filters['section_id']) ? $filters['section_id'] : ''; ?>';
                var options = '<option value="">All Class</option>';
                $.each(data, function(i, obj) {
                    var selected = (selected_class_id !== '' && selected_class_id == obj.id) ? 'selected' : '';
                    options += '<option value="' + obj.id + '" ' + selected + '>' + obj.class + '</option>';
                });
                $('#class_id').html(options);
                if (selected_class_id !== '') {
                    getSectionsByCampus(campus_id, selected_class_id, selected_section_id);
                }
            }
        });
    }

    function getSectionsByCampus(campus_id, class_id, selected_section_id) {
        selected_section_id = selected_section_id || '';
        $.ajax({
            type: "GET",
            url: baseurl + "student/getFilteredSectionsByCampus",
            data: {campus_id: campus_id, class_id: class_id},
            dataType: "json",
            success: function(data) {
                var options = '<option value="">All Section</option>';
                $.each(data, function(i, obj) {
                    var selected = (selected_section_id !== '' && selected_section_id == obj.section_id) ? 'selected' : '';
                    options += '<option value="' + obj.section_id + '" ' + selected + '>' + obj.section + '</option>';
                });
                $('#section_id').html(options);
            }
        });
    }

    function toggleVisibleColumns() {
        $('.display-field-checkbox').each(function() {
            var field_key = $(this).val();
            var $fieldColumns = $('.field-col-' + field_key);
            if ($(this).is(':checked')) {
                $fieldColumns.show().removeClass('noExport');
            } else {
                $fieldColumns.hide().addClass('noExport');
            }
        });
    }
</script>
