<?php
$currency_symbol = $this->customlib->getSchoolCurrencyFormat();
$filters = isset($filters) ? $filters : array();
$collection_rows = isset($collection_rows) ? $collection_rows : array();
$collection_summary = isset($collection_summary) ? $collection_summary : array('records' => 0, 'amount' => 0, 'discount' => 0, 'fine' => 0, 'total' => 0);
$month_options = isset($month_options) ? $month_options : array();
$campus_list = isset($campus_list) ? $campus_list : array();
$classlist = isset($classlist) ? $classlist : array();
$sectionlist = isset($sectionlist) ? $sectionlist : array();
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-list-alt"></i> Fee Collection Details
        </h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-search"></i> Filter Collection</h3>
                        <div class="box-tools pull-right">
                            <a href="<?php echo site_url('studentfee/index'); ?>" class="btn btn-default btn-sm">
                                <i class="fa fa-arrow-left"></i> Back to Collect Fee
                            </a>
                        </div>
                    </div>
                    <form action="<?php echo site_url('studentfee/collectionreport'); ?>" method="post">
                        <div class="box-body">
                            <?php echo $this->customlib->getCSRF(); ?>
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Collection Date</label>
                                        <input type="text" name="collection_date" id="collection_date" class="form-control date" autocomplete="off" placeholder="dd/mm/yyyy" value="<?php echo !empty($filters['collection_date']) ? $filters['collection_date'] : ''; ?>">
                                        
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Collection Month</label>
                                        <select name="collection_month" id="collection_month" class="form-control">
                                            <option value="">Select Month</option>
                                            <?php foreach ($month_options as $month_value => $month_label) { ?>
                                                <option value="<?php echo $month_value; ?>" <?php echo (isset($filters['collection_month']) && (string) $filters['collection_month'] === (string) $month_value) ? 'selected' : ''; ?>>
                                                    <?php echo $month_label; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
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
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('class'); ?></label>
                                        <select name="class_id" id="class_id" class="form-control">
                                            <option value=""><?php echo $this->lang->line('all'); ?> <?php echo $this->lang->line('class'); ?></option>
                                            <?php foreach ($classlist as $class) { ?>
                                                <option value="<?php echo $class['id']; ?>" <?php echo (isset($filters['class_id']) && (string)$filters['class_id'] === (string)$class['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $class['class']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><?php echo $this->lang->line('section'); ?></label>
                                        <select name="section_id" id="section_id" class="form-control">
                                            <option value=""><?php echo $this->lang->line('all'); ?> <?php echo $this->lang->line('section'); ?></option>
                                            <?php foreach ($sectionlist as $section) { ?>
                                                <option value="<?php echo $section['section_id']; ?>" <?php echo (isset($filters['section_id']) && (string)$filters['section_id'] === (string)$section['section_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $section['section']; ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary btn-sm pull-right">
                                <i class="fa fa-search"></i> Search By Date
                            </button>
                            <button type="submit" formaction="<?php echo site_url('studentfee/collectionreportbymonth'); ?>" class="btn btn-success btn-sm pull-right" style="margin-right: 10px;">
                                <i class="fa fa-calendar"></i> Search By Month
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-exchange"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Transactions</span>
                        <span class="info-box-number"><?php echo $collection_summary['records']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-money"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Received Amount</span>
                        <span class="info-box-number"><?php echo $currency_symbol . amountFormat($collection_summary['amount']); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Fine</span>
                        <span class="info-box-number"><?php echo $currency_symbol . amountFormat($collection_summary['fine']); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-red"><i class="fa fa-calculator"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Grand Total</span>
                        <span class="info-box-number"><?php echo $currency_symbol . amountFormat($collection_summary['total']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-table"></i> Received Fee Students List</h3>
                    </div>
                    <div class="box-body">
                        <?php if (!empty($collection_rows)) { ?>
                            <div class="table-responsive">
                                <div class="download_label">Fee Collection Details</div>
                                <table class="table table-striped table-bordered table-hover example">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Campus</th>
                                            <th>Admission No</th>
                                            <th>Student Name</th>
                                            <th>Father Name</th>
                                            <th>Class</th>
                                            <th>Fee Group</th>
                                            <th>Fee Type</th>
                                            <th>Payment Mode</th>
                                            <th>Invoice</th>
                                            <th>Collected By</th>
                                            <th class="text-right">Discount</th>
                                            <th class="text-right">Fine</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($collection_rows as $row) { ?>
                                            <tr>
                                                <td><?php echo $this->customlib->dateformat($row['date']); ?></td>
                                                <td><?php echo $row['campus_name']; ?></td>
                                                <td><?php echo $row['admission_no']; ?></td>
                                                <td><?php echo $row['student_name']; ?></td>
                                                <td><?php echo $row['father_name']; ?></td>
                                                <td><?php echo $row['class_section']; ?></td>
                                                <td><?php echo $row['fee_group']; ?></td>
                                                <td><?php echo $row['fee_type']; ?></td>
                                                <td><?php echo !empty($row['payment_mode']) ? $this->lang->line(strtolower($row['payment_mode'])) : ''; ?></td>
                                                <td><?php echo $row['invoice_no']; ?></td>
                                                <td><?php echo $row['collected_by']; ?></td>
                                                <td class="text-right"><?php echo $currency_symbol . amountFormat($row['discount']); ?></td>
                                                <td class="text-right"><?php echo $currency_symbol . amountFormat($row['fine']); ?></td>
                                                <td class="text-right"><?php echo $currency_symbol . amountFormat($row['amount']); ?></td>
                                                <td class="text-right"><?php echo $currency_symbol . amountFormat($row['total']); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="11" class="text-right">Grand Total</th>
                                            <th class="text-right"><?php echo $currency_symbol . amountFormat($collection_summary['discount']); ?></th>
                                            <th class="text-right"><?php echo $currency_symbol . amountFormat($collection_summary['fine']); ?></th>
                                            <th class="text-right"><?php echo $currency_symbol . amountFormat($collection_summary['amount']); ?></th>
                                            <th class="text-right"><?php echo $currency_symbol . amountFormat($collection_summary['total']); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-info">
                                No fee collection record found for selected filters.
                            </div>
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
    });

    $(document).on('change', '#campus_id', function() {
        var campus_id = $(this).val();
        $('#class_id').html(getDefaultClassOption());
        $('#section_id').html(getDefaultSectionOption());
        toggleCampusDependentFilters(campus_id);
    });

    $(document).on('change', '#class_id', function() {
        $('#section_id').html(getDefaultSectionOption());
        getSectionByCampus($('#campus_id').val(), $(this).val(), 0);
    });

    function toggleCampusDependentFilters(campus_id) {
        var disable_filters = (campus_id === '');
        $('#class_id').prop('disabled', disable_filters);
        $('#section_id').prop('disabled', disable_filters);

        if (!disable_filters) {
            var selected_class_id = '<?php echo isset($filters['class_id']) ? $filters['class_id'] : ''; ?>';
            var selected_section_id = '<?php echo isset($filters['section_id']) ? $filters['section_id'] : ''; ?>';
            getClassByCampus(campus_id, selected_class_id, selected_section_id);
        }
    }

    function getDefaultClassOption() {
        return '<option value=""><?php echo $this->lang->line('all'); ?> <?php echo $this->lang->line('class'); ?></option>';
    }

    function getDefaultSectionOption() {
        return '<option value=""><?php echo $this->lang->line('all'); ?> <?php echo $this->lang->line('section'); ?></option>';
    }

    function getClassByCampus(campus_id, selected_class_id, selected_section_id) {
        var div_data = getDefaultClassOption();

        if (campus_id === '') {
            $('#class_id').html(div_data);
            $('#section_id').html(getDefaultSectionOption());
            return;
        }

        $.ajax({
            type: "GET",
            url: '<?php echo base_url(); ?>studentfee/getClassesByCampus',
            data: {
                'campus_id': campus_id
            },
            dataType: "json",
            beforeSend: function() {
                $('#class_id').addClass('dropdownloading');
            },
            success: function(data) {
                $.each(data, function(i, obj) {
                    var sel = "";
                    if (selected_class_id == obj.id) {
                        sel = "selected";
                    }
                    div_data += "<option value='" + obj.id + "' " + sel + ">" + obj.class + "</option>";
                });
                $('#class_id').html(div_data);
                getSectionByCampus(campus_id, $('#class_id').val(), selected_section_id);
            },
            complete: function() {
                $('#class_id').removeClass('dropdownloading');
            }
        });
    }

    function getSectionByCampus(campus_id, class_id, section_id) {
        var div_data = getDefaultSectionOption();

        if (campus_id !== "" && class_id != "") {
            $.ajax({
                type: "GET",
                url: '<?php echo base_url(); ?>studentfee/getSectionsByCampus',
                data: {
                    'campus_id': campus_id,
                    'class_id': class_id
                },
                dataType: "json",
                beforeSend: function() {
                    $('#section_id').addClass('dropdownloading');
                },
                success: function(data) {
                    $.each(data, function(i, obj) {
                        var sel = "";
                        if (section_id == obj.section_id) {
                            sel = "selected";
                        }
                        div_data += "<option value='" + obj.section_id + "' " + sel + ">" + obj.section + "</option>";
                    });
                    $('#section_id').html(div_data);
                },
                complete: function() {
                    $('#section_id').removeClass('dropdownloading');
                }
            });
        } else {
            $('#section_id').html(div_data);
        }
    }
</script>
