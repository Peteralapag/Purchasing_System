<?php
include '../../../init.php';
?>

<style>
.results {
    position: absolute;
    width: 100%;
    background: #fff;
    border: 1px solid #ddd;
    max-height: 200px;
    overflow-y: auto;
    z-index: 9999;
}
.results div {
    padding: 6px 10px;
    cursor: pointer;
}
.results div:hover {
    background: #f1f5f9;
}
</style>

<div class="mb-2 position-relative">
    <label class="fw-bold">Search Employee</label>
    <input type="text" id="employee_search" class="form-control form-control-sm" placeholder="Type employee name...">
    <div class="results d-none"></div>
</div>

<div class="row">
    <div class="col-md-6 mb-2">
        <label class="fw-bold">Idcode</label>
        <input type="text" id="idcode" class="form-control form-control-sm" readonly>
    </div>

    <div class="col-md-6 mb-2">
        <label class="fw-bold">Username</label>
        <input type="text" id="username" class="form-control form-control-sm">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-2">
        <label class="fw-bold">First Name</label>
        <input type="text" id="firstname" class="form-control form-control-sm" readonly>
    </div>

    <div class="col-md-6 mb-2">
        <label class="fw-bold">Last Name</label>
        <input type="text" id="lastname" class="form-control form-control-sm" readonly>
    </div>
</div>

<div class="d-flex justify-content-end mt-3">
    <button type="button" class="btn btn-success btn-sm" onclick="saveUser()">
        <i class="fa fa-save"></i> Add User
    </button>
</div>

<script>


function saveUser() {
    console.log('saveUser triggered'); // para sure mo-fire
    let data = {
        idcode: $('#idcode').val(),
        firstname: $('#firstname').val(),
        lastname: $('#lastname').val(),
        username: $('#username').val()
    };
    console.log('Data:', data);

    if (!data.idcode || !data.username) {
        swal("Oops...", "Please select employee and enter username", "warning");
        return;
    }

    $.post(
        './Modules/Purchasing_System/actions/settings_save_user.php',
        data,
        function (res) {
            console.log('Response:', res); // tan-awa unsay return
            if (res.status === 'success') {
                swal("Success", res.message, "success");
                $('#formmodal').hide();
                load_data();
            } else {
                swal("Error", res.message, "error");
            }
        },
        'json'
    );  
}



$('#employee_search').keyup(function () {
    let keyword = $(this).val();

    if (keyword.length < 2) {
        $('.results').addClass('d-none').html('');
        return;
    }

    $.post(
        './Modules/Purchasing_System/actions/settings_search_employee.php',
        { keyword: keyword },
        function (data) {
            $('.results').removeClass('d-none').html(data);
        }
    );
});

// click result
$(document).on('click', '.select-employee', function () {
    $('#idcode').val($(this).data('idcode'));
    $('#firstname').val($(this).data('firstname'));
    $('#lastname').val($(this).data('lastname'));

    // auto username suggestion
    $('#username').val(
        $(this).data('firstname').toLowerCase() + '.' +
        $(this).data('lastname').toLowerCase()
    );

    $('#employee_search').val(
        $(this).text()
    );

    $('.results').addClass('d-none').html('');
});
</script>
