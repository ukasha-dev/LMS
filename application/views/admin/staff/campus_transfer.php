<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-exchange"></i> Staff Campus Transfer</h1>
    </section>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form action="<?php echo site_url('admin/staff/campus_transfer_search') ?>" method="post">
                                    <?php echo $this->customlib->getCSRF(); ?>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="role" class="form-control">
                                            <option value="">Select</option>
                                            <?php foreach ($role as $role_value) { ?>
                                                <option value="<?php echo $role_value['id']; ?>"><?php echo $role_value['type']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form action="<?php echo site_url('admin/staff/campus_transfer_search') ?>" method="post">
                                    <?php echo $this->customlib->getCSRF(); ?>
                                    <div class="form-group">
                                        <label>Search By Keyword</label>
                                        <input type="text" name="search_text" class="form-control" placeholder="Search by staff name or id">
                                    </div>
                                    <button type="submit" name="search" value="search_full" class="btn btn-primary btn-sm pull-right">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="box-header ptbnull">
                        <h3 class="box-title"><i class="fa fa-list"></i> Staff List</h3>
                    </div>
                    <div class="box-body">
                        <form class="transfer_form">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row" style="background: #f9f9f9; padding: 15px; margin-bottom: 20px; border: 1px solid #e1e1e1; border-radius: 4px;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Target Campus/Branch</label><small class="req"> *</small>
                                        <select name="target_branch_id" id="target_branch_id" class="form-control">
                                            <option value="">Select Branch</option>
                                            <?php foreach ($branchlist as $branch) { ?>
                                                <option value="<?php echo $branch['id']; ?>"><?php echo $branch['branch_name']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50"><input type="checkbox" id="checkAll"></th>
                                            <th>Staff ID</th>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Department</th>
                                            <th>Designation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($resultlist)) { foreach ($resultlist as $staff) { ?>
                                            <tr>
                                                <td><input name="staff_list[]" type="checkbox" value="<?php echo $staff['id']; ?>"></td>
                                                <td><?php echo $staff['employee_id']; ?></td>
                                                <td><?php echo $staff['name'] . " " . $staff['surname']; ?></td>
                                                <td><?php echo $staff['user_type']; ?></td>
                                                <td><?php echo $staff['department']; ?></td>
                                                <td><?php echo $staff['designation']; ?></td>
                                            </tr>
                                        <?php }} ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="box-footer">
                                <button type="button" class="btn btn-primary pull-right confirm_transfer">
                                    <i class="fa fa-exchange"></i> Transfer Selected Staff
                                </button>
                            </div>
                        </form>
                    </div>
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
                <p>Are you sure you want to move selected staff to the new campus?</p>
                <p class="text-danger">This will migrate profile, payroll, leaves, attendance, documents and timeline records.</p>
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
        $("#checkAll").click(function () {
            $("input[name='staff_list[]']").prop('checked', $(this).prop('checked'));
        });

        $('.confirm_transfer').click(function () {
            if ($("input[name='staff_list[]']:checked").length == 0 || $('#target_branch_id').val() == "") {
                alert("Please select staff and target branch.");
            } else {
                $('#transferModal').modal('show');
            }
        });

        $('#final_move_btn').click(function () {
            var btn = $(this);
            $.ajax({
                type: "POST",
                url: "<?php echo site_url('admin/staff/move_to_campus') ?>",
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
