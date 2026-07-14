<div class="content-wrapper" style="min-height: 946px;">
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-file-pdf-o"></i> Generate Roll Number Slips</h3>
                    </div>
                    <div class="box-body">
                        <form role="form" action="<?php echo site_url('admin/exam_schedule/generate_roll_number_slips') ?>" method="post" target="_blank">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-sm-6 col-lg-3 col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('exam_group'); ?></label><small class="req"> *</small>
                                        <select id="exam_group_id" name="exam_group_id" class="form-control">
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php foreach ($examgrouplist as $ex_group_key => $ex_group_value) { ?>
                                                <option value="<?php echo $ex_group_value->id ?>"><?php echo $ex_group_value->name; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6 col-lg-3 col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('exam') ?></label><small class="req"> *</small>
                                        <select id="exam_id" name="exam_id" class="form-control">
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6 col-lg-3 col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('class') ?></label><small class="req"> *</small>
                                        <select id="class_id" name="class_id" class="form-control">
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                            <?php foreach ($classlist as $class_key => $class_value) { ?>
                                                <option value="<?php echo $class_value['id'] ?>"><?php echo $class_value['class']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-sm-6 col-lg-3 col-md-3">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('section') ?></label>
                                        <select id="section_id" name="section_id" class="form-control">
                                            <option value=""><?php echo $this->lang->line('select'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label>Custom Remarks (Will appear on Roll Slip)</label>
                                        <textarea name="custom_remarks" class="form-control" rows="3" placeholder="Enter instructions here...">1. School timings during Assessment will be remained
2. Result will be announced on specified date</textarea>
                                        <span class="text-info" style="font-size: 11px;">Note: Use Enter for new lines.</span>
                                    </div>
                                </div>
                                
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <button type="submit" name="generate_pdf" value="generate" class="btn btn-primary pull-right btn-sm">
                                            <i class="fa fa-file-pdf-o"></i> Generate Roll Number Slips (PDF)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript">
$(document).ready(function() {
    
    $(document).on('change', '#exam_group_id', function() {
        $('#exam_id').html("");
        var exam_group_id = $(this).val();
        getExamByExamgroup(exam_group_id, 0);
    });
    
    $(document).on('change', '#class_id', function() {
        $('#section_id').html("");
        var class_id = $(this).val();
        getSectionByClass(class_id, 0);
    });
    
    function getSectionByClass(class_id, section_id) {
        if (class_id !== "") {
            $('#section_id').html("");
            var base_url = '<?php echo base_url() ?>';
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            
            $.ajax({
                type: "GET",
                url: base_url + "sections/getByClass",
                data: {'class_id': class_id},
                dataType: "json",
                beforeSend: function() {
                    $('#section_id').addClass('dropdownloading');
                },
                success: function(data) {
                    $.each(data, function(i, obj) {
                        var sel = "";
                        if (section_id === obj.section_id) {
                            sel = "selected";
                        }
                        div_data += "<option value=" + obj.section_id + " " + sel + ">" + obj.section + "</option>";
                    });
                    $('#section_id').append(div_data);
                },
                complete: function() {
                    $('#section_id').removeClass('dropdownloading');
                }
            });
        }
    }
    
    function getExamByExamgroup(exam_group_id, exam_id) {
        if (exam_group_id !== "") {
            $('#exam_id').html("");
            var base_url = '<?php echo base_url() ?>';
            var div_data = '<option value=""><?php echo $this->lang->line('select'); ?></option>';
            
            $.ajax({
                type: "POST",
                url: base_url + "admin/examgroup/getExamByExamgroup",
                data: {'exam_group_id': exam_group_id},
                dataType: "json",
                beforeSend: function() {
                    $('#exam_id').addClass('dropdownloading');
                },
                success: function(data) {
                    $.each(data, function(i, obj) {
                        var sel = "";
                        if (exam_id === obj.id) {
                            sel = "selected";
                        }
                        div_data += "<option value=" + obj.id + " " + sel + ">" + obj.exam + "</option>";
                    });
                    $('#exam_id').append(div_data);
                },
                complete: function() {
                    $('#exam_id').removeClass('dropdownloading');
                }
            });
        }
    }
});
</script>