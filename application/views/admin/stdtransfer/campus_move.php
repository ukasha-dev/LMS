<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-exchange"></i> Student Campus Transfer</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
                    </div>
                    <form action="<?php echo site_url('admin/stdtransfer/campus_transfer_search') ?>" method="post">
                        <div class="box-body">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Class</label><small class="req"> *</small>
                                        <select id="class_id" name="class_id" class="form-control" required>
                                            <option value="">Select</option>
                                            <?php foreach ($classlist as $class) { ?>
                                                <option value="<?php echo $class['id'] ?>" <?php echo set_select('class_id', $class['id']); ?>><?php echo $class['class'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Section</label><small class="req"> *</small>
                                        <select id="section_id" name="section_id" class="form-control"
                                            data-selected="<?php echo set_value('section_id'); ?>" required>
                                            <option value="">Select</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary btn-sm pull-right"><i class="fa fa-search"></i>
                                Search</button>
                        </div>
                    </form>

                    <?php if (isset($resultlist)) { ?>
                        <div class="box-header ptbnull">
                            <h3 class="box-title"><i class="fa fa-list"></i> Student List</h3>
                        </div>
                        <div class="box-body">
                            <form class="transfer_form">
                                <?php echo $this->customlib->getCSRF(); ?>
                                <div class="row"
                                    style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border: 1px solid #e1e1e1; border-radius: 4px;">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Target Campus/Branch</label><small class="req"> *</small>
                                            <select name="target_branch_id" id="target_branch_id" class="form-control">
                                                <option value="">Select Branch</option>
                                                <?php
                                                foreach ($branchlist as $branch) {
                                                    $b_id = isset($branch['id']) ? $branch['id'] : (isset($branch->id) ? $branch->id : '');
                                                    $b_name = isset($branch['branch_name']) ? $branch['branch_name'] : (isset($branch->branch_name) ? $branch->branch_name : '');
                                                    echo '<option value="' . $b_id . '">' . $b_name . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Target Class</label><small class="req"> *</small>
                                            <select name="target_class_id" id="target_class_id" class="form-control">
                                                <option value="">Select Branch First</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Target Section</label><small class="req"> *</small>
                                            <select name="target_section_id" id="target_section_id" class="form-control">
                                                <option value="">Select Class First</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Target Session</label><small class="req"> *</small>
                                            <select name="target_session_name" id="target_session_name"
                                                class="form-control">
                                                <option value="">Select Session</option>
                                                <?php
                                                $sessions = ["2016-17", "2017-18", "2018-19", "2019-20", "2020-21", "2021-22", "2022-23", "2023-24", "2024-25", "2025-26", "2026-27", "2027-28", "2028-29", "2029-30"];
                                                foreach ($sessions as $s) {
                                                    echo '<option value="' . $s . '">' . $s . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th width="50"><input type="checkbox" id="checkAll"></th>
                                                <th>Admission No</th>
                                                <th>Student Name</th>
                                                <th>Father Name</th>
                                                <th>Current Campus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultlist as $student) { ?>
                                                <tr>
                                                    <td><input name="student_list[]" type="checkbox"
                                                            value="<?php echo $student['id']; ?>"></td>
                                                    <td><?php echo $student['admission_no']; ?></td>
                                                    <td><?php echo $student['firstname'] . " " . $student['lastname']; ?></td>
                                                    <td><?php echo $student['father_name']; ?></td>
                                                    <td><span
                                                            class="label label-info"><?php echo $this->customlib->getSchoolName(); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-footer">
                                    <button type="button" class="btn btn-primary pull-right confirm_transfer">
                                        <i class="fa fa-exchange"></i> Transfer Selected Students
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="transferModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #3c8dbc; color: #fff;">
                <h4 class="modal-title">Confirm Transfer</h4>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to move selected students to the new campus?</p>
                <p class="text-danger">This will migrate data across databases.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="final_move_btn">Yes, Transfer Now</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var base_url = '<?php echo base_url(); ?>';

        // --- 1. FUNCTION: Sections Load Karne Ke Liye (Current Branch) ---
        function getSectionByClass(class_id, section_id) {
            if (class_id != "") {
                $('#section_id').html('<option value="">Loading...</option>');
                $.ajax({
                    type: "GET",
                    url: base_url + "sections/getByClass",
                    data: { 'class_id': class_id },
                    dataType: "json",
                    success: function (data) {
                        var div_data = '<option value="">Select</option>';
                        $.each(data, function (i, obj) {
                            var sel = "";
                            // Agar search ke baad section_id match ho to usay select rakho
                            if (section_id == obj.section_id) {
                                sel = "selected";
                            }
                            div_data += "<option value='" + obj.section_id + "' " + sel + ">" + obj.section + "</option>";
                        });
                        $('#section_id').html(div_data);
                    }
                });
            }
        }

        // --- 2. AUTO-LOAD: Search ke baad data wapis dikhane ke liye ---
        var pre_class_id = $('#class_id').val();
        var pre_section_id = $('#section_id').data('selected'); // HTML mein data-selected use karein

        if (pre_class_id != '') {
            getSectionByClass(pre_class_id, pre_section_id);
        }

        // --- 3. EVENT: Jab Class manually change ho ---
        $(document).on('change', '#class_id', function () {
            var class_id = $(this).val();
            getSectionByClass(class_id, "");
        });

        // --- 4. TARGET BRANCH: Fetch Classes ---
        $(document).on('change', '#target_branch_id', function () {
            var branch_id = $(this).val();
            $('#target_class_id').html('<option value="">Loading...</option>');
            $('#target_section_id').html('<option value="">Select Class First</option>');

            if (branch_id === "") {
                $('#target_class_id').html('<option value="">Select Branch First</option>');
                return;
            }

            $.ajax({
                type: "POST",
                url: "<?php echo site_url('admin/stdtransfer/getClassesByBranch') ?>",
                data: { 'branch_id': branch_id },
                dataType: "json",
                cache: false,
                success: function (data) {
                    var options = '<option value="">Select Class</option>';
                    if (data && data.length > 0) {
                        $.each(data, function (index, obj) {
                            var className = obj.class || obj.class_name || "Unknown Class";
                            options += '<option value="' + obj.id + '">' + className + '</option>';
                        });
                        $('#target_class_id').html(options);
                    } else {
                        $('#target_class_id').html('<option value="">No Classes Found</option>');
                    }
                }
            });
        });

        // --- 5. TARGET CLASS: Fetch Sections ---
        $(document).on('change', '#target_class_id', function () {
            var class_id = $(this).val();
            var branch_id = $('#target_branch_id').val();

            if (class_id == "") {
                $('#target_section_id').html('<option value="">Select Class First</option>');
                return;
            }

            $('#target_section_id').html('<option value="">Loading Sections...</option>');

            $.ajax({
                type: "POST",
                url: "<?php echo site_url('admin/stdtransfer/getSectionsByBranch') ?>",
                data: { 'branch_id': branch_id, 'class_id': class_id },
                dataType: "json",
                success: function (data) {
                    var div_data = '<option value="">Select Section</option>';
                    if (data && data.length > 0) {
                        $.each(data, function (i, obj) {
                            div_data += "<option value='" + obj.id + "'>" + obj.section + "</option>";
                        });
                    } else {
                        div_data = '<option value="">No Sections Found</option>';
                    }
                    $('#target_section_id').html(div_data);
                }
            });
        });

        // --- 6. CHECKBOX & MODAL LOGIC ---
        $("#checkAll").click(function () {
            $("input[name='student_list[]']").prop('checked', $(this).prop('checked'));
        });

        $('.confirm_transfer').click(function () {
            if ($("input[name='student_list[]']:checked").length == 0 || $('#target_branch_id').val() == "" || $('#target_class_id').val() == "" || $('#target_section_id').val() == "" || $('#target_session_name').val() == "") {
                alert("Please select students, session, class, section and target branch.");
            } else {
                $('#transferModal').modal('show');
            }
        });

        $('#final_move_btn').click(function () {
            var btn = $(this);
            $.ajax({
                type: "POST",
                url: "<?php echo site_url('admin/stdtransfer/move_to_campus') ?>",
                data: $(".transfer_form").serialize(),
                dataType: 'json',
                beforeSend: function () { btn.prop('disabled', true).text('Moving...'); },
                success: function (data) {
                    alert(data.msg);
                    if (data.status == "success") { location.reload(); }
                    else { btn.prop('disabled', false).text('Yes, Transfer Now'); }
                },
                error: function () {
                    alert("System Error: Migration failed.");
                    btn.prop('disabled', false).text('Yes, Transfer Now');
                }
            });
        });
    });
</script>