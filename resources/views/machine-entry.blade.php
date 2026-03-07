<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7">
    <title>Machine Lot Entry</title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            background-color: #e0e0e0;
            padding: 5px;
        }
        
        .container {
            width: 950px;
            background-color: #ffffff;
            border: 2px solid #333;
            padding: 8px;
        }
        
        .header {
            background-color: #003366;
            color: #ffffff;
            padding: 6px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .section {
            border: 1px solid #999;
            padding: 5px;
            margin-bottom: 5px;
            background-color: #f9f9f9;
        }
        
        .section-title {
            background-color: #666;
            color: #fff;
            padding: 3px 5px;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .form-row {
            margin-bottom: 3px;
            clear: both;
        }
        
        .form-label {
            display: inline-block;
            width: 80px;
            font-weight: bold;
            vertical-align: middle;
            font-size: 11px;
        }
        
        .form-input {
            display: inline-block;
            vertical-align: middle;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            padding: 2px;
            border: 1px solid #999;
            font-size: 11px;
            height: 20px;
        }
        
        .input-small {
            width: 60px;
        }
        
        .input-medium {
            width: 120px;
        }
        
        .input-large {
            width: 180px;
        }
        
        .btn {
            padding: 2px 10px;
            border: 1px solid #333;
            background-color: #ddd;
            cursor: pointer;
            font-size: 11px;
            height: 20px;
            vertical-align: middle;
        }
        
        .btn:hover {
            background-color: #ccc;
        }
        
        .btn-primary {
            background-color: #003366;
            color: #fff;
            font-weight: bold;
            padding: 6px 20px;
            height: auto;
        }
        
        .btn-primary:hover {
            background-color: #004488;
        }
        
        .btn-success {
            background-color: #006600;
            color: #fff;
        }
        
        .btn-success:hover {
            background-color: #008800;
        }
        
        .error {
            color: #cc0000;
            font-size: 10px;
            margin-left: 85px;
        }
        
        .success {
            background-color: #ccffcc;
            border: 1px solid #00cc00;
            padding: 6px;
            margin-bottom: 6px;
            color: #006600;
            font-size: 11px;
        }
        
        .warning {
            background-color: #ffffcc;
            border: 1px solid #ffcc00;
            padding: 6px;
            margin-bottom: 6px;
            color: #996600;
            font-size: 11px;
        }
        
        .info-box {
            background-color: #e6f2ff;
            border: 1px solid #0066cc;
            padding: 4px;
            margin-top: 5px;
            font-size: 10px;
        }
        
        .readonly {
            background-color: #e9e9e9;
            color: #666;
        }
        
        .button-group {
            text-align: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px solid #999;
        }
        
        .equipment-row {
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 4px;
            margin-bottom: 3px;
        }
        
        .equipment-number {
            display: inline-block;
            width: 20px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table td {
            padding: 2px;
            font-size: 10px;
        }
        
        .compact-grid {
            display: block;
        }
        
        .compact-col {
            display: inline-block;
            vertical-align: top;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            MACHINE LOT ENTRY SYSTEM
        </div>
        
        @if(session('success'))
        <script type="text/javascript">
            // Immediately try to close the window
            setTimeout(function() {
                window.close();
            }, 1000);
            
            // Backup method
            setTimeout(function() {
                window.open('', '_self', '');
                window.close();
            }, 1500);
            
            // Force close for IE
            setTimeout(function() {
                window.opener = 'X';
                self.close();
            }, 2000);
        </script>
        <div class="success" style="text-align: center; padding: 30px; font-size: 14px;">
            ✓ {{ session('success') }}
            <br><br>
            <span style="font-size: 11px;">Closing window...</span>
        </div>
        @endif
        
        @if(session('warning'))
        <div class="warning">
            {{ session('warning') }}
        </div>
        @endif
        
        @if($errors->any())
        <div class="error" style="margin-left: 0; margin-bottom: 10px; padding: 10px; border: 1px solid #cc0000; background-color: #ffcccc;">
            <strong>Please correct the following errors:</strong>
            <ul style="margin-left: 20px; margin-top: 5px;">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <form method="POST" action="{{ route('machine_entry.store') }}" onsubmit="return validateForm()">
            @csrf
            
            <!-- Lot Information Section -->
            <div class="section">
                <div class="section-title">LOT INFORMATION</div>
                
                <table style="width: 100%;">
                    <tr>
                        <td width="160">
                            <strong>Lot No: *</strong><br>
                            <input type="text" name="lot_id" id="lot_id" class="input-medium" 
                                   value="{{ old('lot_id') }}" maxlength="15" required 
                                   onblur="lookupLot()" style="text-transform: uppercase;">
                            <input type="button" value="?" class="btn" onclick="lookupLot()">
                        </td>
                        <td width="140">
                            <strong>Lot Qty: *</strong><br>
                            <input type="text" name="lot_qty" id="lot_qty" class="input-medium readonly" 
                                   value="{{ old('lot_qty') }}" readonly>
                        </td>
                        <td width="80">
                            <strong>Size:</strong><br>
                            <input type="text" id="lot_size_display" class="input-small readonly" readonly>
                            <input type="hidden" name="lot_size" id="lot_size" value="{{ old('lot_size', '10') }}">
                        </td>
                        <td width="140">
                            <strong>Work Type:</strong><br>
                            <input type="text" name="work_type" id="work_type" class="input-medium readonly" 
                                   value="{{ old('work_type', 'NORMAL') }}" readonly>
                        </td>
                        <td>
                            &nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td colspan="5" style="padding-top: 2px;">
                            <div id="lot_error" class="error" style="margin-left: 0;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Lot Type: *</strong><br>
                            <input type="radio" name="lot_type" value="MAIN" checked onchange="handleLotTypeChange()"> MAIN
                            <input type="radio" name="lot_type" value="WL/RW" onchange="handleLotTypeChange()"> WL/RW
                            <input type="radio" name="lot_type" value="RL/LY" onchange="handleLotTypeChange()"> RL/LY
                        </td>
                        <td colspan="2">
                            <strong>Model:</strong><br>
                            <input type="text" name="model_15" id="model_15" style="width: 200px;" class="readonly" 
                                   value="{{ old('model_15') }}" readonly>
                        </td>
                        <td width="80">
                            <strong>LIPAS:</strong><br>
                            <input type="text" name="lipas_yn" id="lipas_yn" class="input-small readonly" 
                                   value="{{ old('lipas_yn', 'N') }}" readonly>
                        </td>
                        <td>
                            &nbsp;
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Employee Information -->
            <div class="section">
                <div class="section-title">EMPLOYEE INFORMATION</div>
                
                <table style="width: 100%;">
                    <tr>
                        <td>
                            <strong>Employee ID: *</strong><br>
                            <input type="text" name="employee_id" id="employee_id" class="input-medium" 
                                   value="{{ old('employee_id') }}" maxlength="10" required
                                   onblur="lookupEmployee()">
                            <input type="button" value="?" class="btn" onclick="lookupEmployee()">
                            <span id="employee_name" style="margin-left: 5px; font-weight: bold;"></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id="employee_error" class="error" style="margin-left: 0;"></div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Ongoing Equipment Section (shown when lot has ongoing entry) -->
            <div class="section" id="ongoing_section" style="display: none; background-color: #ffffcc;">
                <div class="section-title" style="background-color: #ff9900;">ONGOING EQUIPMENT (Machine Sharing)</div>
                <div id="ongoing_equipment_list"></div>
                <div class="info-box" style="background-color: #fff3cd; border-color: #ff9900;">
                    <strong>Note:</strong> This lot is currently ongoing. You can add more machines below to share the workload. The endtime will be recalculated automatically.
                </div>
            </div>

            <!-- Machine Assignment Section -->
            <div class="section">
                <div class="section-title" id="machine_section_title">MACHINE ASSIGNMENT (Max 3 machines)</div>
                
                <div id="equipment_container">
                    <!-- Equipment Row 1 -->
                    <div class="equipment-row" id="equipment_1">
                        <table>
                            <tr>
                                <td width="20"><span class="equipment-number">1</span></td>
                                <td width="160">
                                    Machine No: *<br>
                                    <input type="text" name="equipment[0][eqp_no]" id="eqp_no_0" 
                                           class="input-medium" maxlength="10" required
                                           onblur="lookupEquipment(0)" style="text-transform: uppercase;">
                                    <input type="button" value="?" class="btn" onclick="lookupEquipment(0)">
                                </td>
                                <td width="50" style="text-align: center;">
                                    Fix MC:<br>
                                    <input type="checkbox" id="fixed_machines" onchange="toggleFixedMachines()" style="margin-top: 2px;">
                                </td>
                                <td width="60">
                                    Size:<br>
                                    <input type="text" id="eqp_size_0" class="input-small readonly" readonly>
                                </td>
                                <td width="100">
                                    Capacity:<br>
                                    <input type="text" id="eqp_capa_0" class="input-medium readonly" readonly>
                                </td>
                                <td width="70">
                                    NG %:<br>
                                    <input type="number" name="equipment[0][ng_percent]" id="ng_percent_0" 
                                           class="input-small" value="0" min="0" max="100" step="0.1">
                                </td>
                                <td>
                                    Start Time: *<br>
                                    <input type="text" name="equipment[0][start_time]" id="start_time_0" 
                                           class="input-large" placeholder="YYYY-MM-DD HH:MM" required>
                                    <input type="button" value="NOW" class="btn btn-success" onclick="setCurrentTime(0)">
                                </td>
                            </tr>
                        </table>
                        <div id="eqp_error_0" class="error"></div>
                    </div>
                </div>
                
                <div style="margin-top: 5px;">
                    <input type="button" value="+ ADD MACHINE" class="btn" onclick="addEquipmentRow()" id="add_btn">
                </div>
                
                <div class="info-box" id="endtime_info">
                    <strong>Est. Endtime:</strong> <span id="est_endtime_display">Enter machine and start time</span>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="button-group">
                <input type="submit" value="SAVE LOT ENTRY" class="btn btn-primary" id="submit_btn">
                <input type="button" value="CLEAR FORM" class="btn" onclick="clearForm()" style="margin-left: 5px;">
            </div>
        </form>
    </div>
    
    <script type="text/javascript">
        var equipmentCount = 1;
        var maxEquipment = 3;
        var ongoingEquipment = []; // Store ongoing equipment for validation
        var isOngoingLot = false;
        
        // Load fixed machines on page load
        window.onload = function() {
            loadFixedMachines();
        };
        
        function toggleFixedMachines() {
            var checkbox = document.getElementById('fixed_machines');
            
            if (checkbox.checked) {
                // Save current equipment numbers to localStorage
                saveFixedMachines();
                alert('Equipment numbers saved! They will be loaded automatically next time.');
            } else {
                // Clear saved equipment numbers
                if (confirm('Clear saved equipment numbers?')) {
                    clearFixedMachines();
                    alert('Saved equipment numbers cleared.');
                } else {
                    checkbox.checked = true;
                }
            }
        }
        
        function saveFixedMachines() {
            var machines = [];
            for (var i = 0; i < equipmentCount; i++) {
                var eqpField = document.getElementById('eqp_no_' + i);
                if (eqpField && eqpField.value) {
                    machines.push(eqpField.value);
                }
            }
            
            try {
                localStorage.setItem('fixed_machines', machines.join(','));
                localStorage.setItem('fixed_machines_enabled', 'true');
            } catch (e) {
                // localStorage not available in IE6, use cookie fallback
                document.cookie = 'fixed_machines=' + machines.join(',') + '; expires=Fri, 31 Dec 9999 23:59:59 GMT';
                document.cookie = 'fixed_machines_enabled=true; expires=Fri, 31 Dec 9999 23:59:59 GMT';
            }
        }
        
        function loadFixedMachines() {
            var enabled = false;
            var machinesStr = '';
            
            try {
                enabled = localStorage.getItem('fixed_machines_enabled') === 'true';
                machinesStr = localStorage.getItem('fixed_machines') || '';
            } catch (e) {
                // Fallback to cookies for IE6
                enabled = getCookie('fixed_machines_enabled') === 'true';
                machinesStr = getCookie('fixed_machines') || '';
            }
            
            if (enabled && machinesStr) {
                var machines = machinesStr.split(',');
                document.getElementById('fixed_machines').checked = true;
                
                // Load first machine
                if (machines.length > 0 && machines[0]) {
                    document.getElementById('eqp_no_0').value = machines[0];
                    lookupEquipment(0);
                }
                
                // Add and load additional machines
                for (var i = 1; i < machines.length && i < maxEquipment; i++) {
                    if (machines[i]) {
                        addEquipmentRow();
                        document.getElementById('eqp_no_' + i).value = machines[i];
                        lookupEquipment(i);
                    }
                }
            }
        }
        
        function clearFixedMachines() {
            try {
                localStorage.removeItem('fixed_machines');
                localStorage.removeItem('fixed_machines_enabled');
            } catch (e) {
                // Clear cookies
                document.cookie = 'fixed_machines=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = 'fixed_machines_enabled=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            }
        }
        
        function getCookie(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
        
        function lookupLot() {
            var lotId = document.getElementById('lot_id').value.toUpperCase();
            document.getElementById('lot_id').value = lotId;
            
            if (!lotId) return;
            
            // Reset ongoing state
            isOngoingLot = false;
            ongoingEquipment = [];
            document.getElementById('ongoing_section').style.display = 'none';
            document.getElementById('machine_section_title').innerHTML = 'MACHINE ASSIGNMENT (Max 3 machines)';
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/updatewip/lookup-simple?lot_id=' + encodeURIComponent(lotId), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        var data = eval('(' + xhr.responseText + ')');
                        if (data.success) {
                            document.getElementById('lot_qty').value = data.lot.lot_qty || '';
                            document.getElementById('lot_size').value = data.lot.lot_size || '10';
                            document.getElementById('lot_size_display').value = getSizeDisplay(data.lot.lot_size || '10');
                            document.getElementById('model_15').value = data.lot.model_15 || '';
                            document.getElementById('work_type').value = data.lot.work_type || 'NORMAL';
                            document.getElementById('lipas_yn').value = data.lot.lipas_yn || 'N';
                            document.getElementById('lot_error').innerHTML = '';
                            
                            // Handle ongoing lot
                            if (data.ongoing) {
                                isOngoingLot = true;
                                ongoingEquipment = data.ongoing_equipment || [];
                                displayOngoingEquipment(data.ongoing_equipment, data.lot.est_endtime);
                                document.getElementById('lot_error').innerHTML = '<span style="color: #ff9900; font-weight: bold;">' + data.message + '</span>';
                                
                                // Update lot type to match ongoing lot
                                var lotTypeRadios = document.getElementsByName('lot_type');
                                for (var i = 0; i < lotTypeRadios.length; i++) {
                                    if (lotTypeRadios[i].value === data.lot.lot_type) {
                                        lotTypeRadios[i].checked = true;
                                    }
                                }
                            } else if (data.warning) {
                                document.getElementById('lot_error').innerHTML = '<span style="color: #996600;">' + data.warning + '</span>';
                            }
                        } else {
                            document.getElementById('lot_error').innerHTML = data.message || 'Lot not found';
                        }
                    } else {
                        document.getElementById('lot_error').innerHTML = 'Error looking up lot';
                    }
                }
            };
            xhr.send();
        }
        
        function displayOngoingEquipment(equipment, estEndtime) {
            if (!equipment || equipment.length === 0) return;
            
            var html = '<div style="background-color: #fff; padding: 5px; border: 1px solid #ff9900;">';
            html += '<strong>Current Equipment on this Lot:</strong><br><br>';
            
            for (var i = 0; i < equipment.length; i++) {
                var eqp = equipment[i];
                html += '<div style="margin-bottom: 3px; padding: 3px; background-color: #f9f9f9; border: 1px solid #ddd;">';
                html += '<strong>' + (i + 1) + '.</strong> ';
                html += '<strong>Machine:</strong> ' + eqp.eqp_no + ' | ';
                html += '<strong>Capacity:</strong> ' + (eqp.oee_capa || 0) + ' | ';
                html += '<strong>Size:</strong> ' + (eqp.size || '-') + ' | ';
                html += '<strong>NG%:</strong> ' + (eqp.ng_percent || 0) + ' | ';
                html += '<strong>Start:</strong> ' + (eqp.start_time || '-');
                html += '</div>';
            }
            
            if (estEndtime) {
                html += '<br><div style="padding: 3px; background-color: #e6f2ff; border: 1px solid #0066cc;">';
                html += '<strong>Current Est. Endtime:</strong> ' + estEndtime;
                html += '</div>';
            }
            
            html += '</div>';
            
            document.getElementById('ongoing_equipment_list').innerHTML = html;
            document.getElementById('ongoing_section').style.display = 'block';
            document.getElementById('machine_section_title').innerHTML = 'ADD MORE MACHINES (Machine Sharing)';
            
            // Update submit button text
            var submitBtn = document.getElementById('submit_btn');
            if (submitBtn) {
                submitBtn.value = 'ADD MACHINES & RECALCULATE ENDTIME';
            }
        }
        
        function lookupEmployee() {
            var employeeId = document.getElementById('employee_id').value;
            if (!employeeId) return;
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/employee/lookup-simple?employee_id=' + encodeURIComponent(employeeId), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        var data = eval('(' + xhr.responseText + ')');
                        if (data.success) {
                            document.getElementById('employee_name').innerHTML = data.employee.employee_name;
                            document.getElementById('employee_error').innerHTML = '';
                        } else {
                            document.getElementById('employee_name').innerHTML = '';
                            document.getElementById('employee_error').innerHTML = 'Employee not found';
                        }
                    }
                }
            };
            xhr.send();
        }
        
        function lookupEquipment(index) {
            var eqpNo = document.getElementById('eqp_no_' + index).value.toUpperCase();
            document.getElementById('eqp_no_' + index).value = eqpNo;
            
            if (!eqpNo) return;
            
            // Check for duplicate machine numbers (including ongoing equipment)
            if (checkDuplicateMachine(index, eqpNo)) {
                document.getElementById('eqp_error_' + index).innerHTML = 'Duplicate machine number! This machine is already added.';
                document.getElementById('eqp_capa_' + index).value = '';
                var sizeField = document.getElementById('eqp_size_' + index);
                if (sizeField) {
                    sizeField.value = '';
                }
                return;
            }
            
            // Try with VI prefix if 3 digits
            if (eqpNo.length == 3 && !isNaN(eqpNo)) {
                eqpNo = 'VI' + eqpNo;
                document.getElementById('eqp_no_' + index).value = eqpNo;
                
                // Check again with VI prefix
                if (checkDuplicateMachine(index, eqpNo)) {
                    document.getElementById('eqp_error_' + index).innerHTML = 'Duplicate machine number! This machine is already added.';
                    document.getElementById('eqp_capa_' + index).value = '';
                    var sizeField = document.getElementById('eqp_size_' + index);
                    if (sizeField) {
                        sizeField.value = '';
                    }
                    return;
                }
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/equipment/lookup-simple?eqp_no=' + encodeURIComponent(eqpNo), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        var data = eval('(' + xhr.responseText + ')');
                        if (data.success) {
                            document.getElementById('eqp_capa_' + index).value = data.equipment.oee_capa || '0';
                            var sizeField = document.getElementById('eqp_size_' + index);
                            if (sizeField) {
                                sizeField.value = data.equipment.size || '-';
                            }
                            document.getElementById('eqp_error_' + index).innerHTML = '';
                            calculateEndtime();
                        } else {
                            document.getElementById('eqp_capa_' + index).value = '';
                            var sizeField = document.getElementById('eqp_size_' + index);
                            if (sizeField) {
                                sizeField.value = '';
                            }
                            document.getElementById('eqp_error_' + index).innerHTML = 'Equipment not found';
                        }
                    }
                }
            };
            xhr.send();
        }
        
        function checkDuplicateMachine(currentIndex, machineNo) {
            if (!machineNo) return false;
            
            var upperMachineNo = machineNo.toUpperCase();
            
            // Check against ongoing equipment
            if (isOngoingLot) {
                for (var i = 0; i < ongoingEquipment.length; i++) {
                    if (ongoingEquipment[i].eqp_no.toUpperCase() === upperMachineNo) {
                        return true;
                    }
                }
            }
            
            // Check against current form equipment
            for (var i = 0; i < equipmentCount; i++) {
                if (i !== currentIndex) {
                    var otherField = document.getElementById('eqp_no_' + i);
                    if (otherField && otherField.value.toUpperCase() === upperMachineNo) {
                        return true;
                    }
                }
            }
            return false;
        }
        
        function addEquipmentRow() {
            if (equipmentCount >= maxEquipment) {
                alert('Maximum ' + maxEquipment + ' machines allowed');
                return;
            }
            
            var container = document.getElementById('equipment_container');
            var newRow = document.createElement('div');
            newRow.className = 'equipment-row';
            newRow.id = 'equipment_' + (equipmentCount + 1);
            
            newRow.innerHTML = '<table><tr>' +
                '<td width="20"><span class="equipment-number">' + (equipmentCount + 1) + '</span></td>' +
                '<td width="160">Machine No: *<br>' +
                '<input type="text" name="equipment[' + equipmentCount + '][eqp_no]" id="eqp_no_' + equipmentCount + '" ' +
                'class="input-medium" maxlength="10" required onblur="lookupEquipment(' + equipmentCount + ')" style="text-transform: uppercase;">' +
                '<input type="button" value="?" class="btn" onclick="lookupEquipment(' + equipmentCount + ')"></td>' +
                '<td width="50">&nbsp;</td>' +
                '<td width="60">Size:<br>' +
                '<input type="text" id="eqp_size_' + equipmentCount + '" class="input-small readonly" readonly></td>' +
                '<td width="100">Capacity:<br>' +
                '<input type="text" id="eqp_capa_' + equipmentCount + '" class="input-medium readonly" readonly></td>' +
                '<td width="70">NG %:<br>' +
                '<input type="number" name="equipment[' + equipmentCount + '][ng_percent]" id="ng_percent_' + equipmentCount + '" ' +
                'class="input-small" value="0" min="0" max="100" step="0.1"></td>' +
                '<td>Start Time: *<br>' +
                '<input type="text" name="equipment[' + equipmentCount + '][start_time]" id="start_time_' + equipmentCount + '" ' +
                'class="input-large" placeholder="YYYY-MM-DD HH:MM" required>' +
                '<input type="button" value="NOW" class="btn btn-success" onclick="setCurrentTime(' + equipmentCount + ')"></td>' +
                '</tr></table>' +
                '<div id="eqp_error_' + equipmentCount + '" class="error"></div>';
                'class="input-small" value="0" min="0" max="100" step="0.1"></td>' +
                '<td>Start Time: *<br>' +
                '<input type="text" name="equipment[' + equipmentCount + '][start_time]" id="start_time_' + equipmentCount + '" ' +
                'class="input-large" placeholder="YYYY-MM-DD HH:MM" required>' +
                '<input type="button" value="NOW" class="btn btn-success" onclick="setCurrentTime(' + equipmentCount + ')"></td>' +
                '</tr></table>' +
                '<div id="eqp_error_' + equipmentCount + '" class="error"></div>';
            
            container.appendChild(newRow);
            equipmentCount++;
            
            if (equipmentCount >= maxEquipment) {
                document.getElementById('add_btn').disabled = true;
                document.getElementById('add_btn').style.backgroundColor = '#ccc';
            }
        }
        
        function setCurrentTime(index) {
            var now = new Date();
            var year = now.getFullYear();
            var month = ('0' + (now.getMonth() + 1)).slice(-2);
            var day = ('0' + now.getDate()).slice(-2);
            var hours = ('0' + now.getHours()).slice(-2);
            var minutes = ('0' + now.getMinutes()).slice(-2);
            
            var timeString = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
            document.getElementById('start_time_' + index).value = timeString;
            calculateEndtime();
        }
        
        function getSizeDisplay(size) {
            var sizeMap = {
                '03': '0603',
                '05': '1005',
                '10': '1608',
                '21': '2012',
                '31': '3216',
                '32': '3225'
            };
            return sizeMap[size] || size;
        }
        
        function handleLotTypeChange() {
            var lotType = document.querySelector('input[name="lot_type"]:checked').value;
            var lotQtyField = document.getElementById('lot_qty');
            
            if (lotType == 'RL/LY') {
                lotQtyField.readOnly = false;
                lotQtyField.className = 'input-medium';
            } else {
                lotQtyField.readOnly = true;
                lotQtyField.className = 'input-medium readonly';
            }
        }
        
        function calculateEndtime() {
            // Simple calculation display
            var lotQty = parseInt(document.getElementById('lot_qty').value) || 0;
            if (lotQty == 0) return;
            
            var totalCapa = 0;
            var latestTime = null;
            
            for (var i = 0; i < equipmentCount; i++) {
                var capaField = document.getElementById('eqp_capa_' + i);
                var timeField = document.getElementById('start_time_' + i);
                
                if (capaField && timeField && capaField.value && timeField.value) {
                    var capa = parseInt(capaField.value) || 0;
                    totalCapa += capa;
                    
                    if (!latestTime || timeField.value > latestTime) {
                        latestTime = timeField.value;
                    }
                }
            }
            
            if (totalCapa > 0 && latestTime) {
                var capaPerMin = totalCapa / (24 * 60);
                var processingMins = lotQty / capaPerMin;
                var processingHours = (processingMins / 60).toFixed(1);
                
                document.getElementById('est_endtime_display').innerHTML = 
                    'Approx. ' + processingHours + ' hours from latest start time';
            }
        }
        
        function validateForm() {
            var employeeName = document.getElementById('employee_name').innerHTML;
            if (!employeeName) {
                alert('Please lookup and validate Employee ID first');
                return false;
            }
            
            var lotQty = document.getElementById('lot_qty').value;
            if (!lotQty || lotQty == '0') {
                alert('Please lookup Lot ID first to get Lot Quantity');
                return false;
            }
            
            // Check for duplicate machine numbers (including ongoing equipment)
            var machines = [];
            var hasDuplicate = false;
            var duplicateMachine = '';
            
            // Add ongoing equipment to check list
            if (isOngoingLot) {
                for (var i = 0; i < ongoingEquipment.length; i++) {
                    machines.push(ongoingEquipment[i].eqp_no.toUpperCase());
                }
            }
            
            // Check new equipment entries
            for (var i = 0; i < equipmentCount; i++) {
                var eqpField = document.getElementById('eqp_no_' + i);
                if (eqpField && eqpField.value) {
                    var machineNo = eqpField.value.toUpperCase();
                    
                    // Check if this machine already exists in the array
                    for (var j = 0; j < machines.length; j++) {
                        if (machines[j] === machineNo) {
                            hasDuplicate = true;
                            duplicateMachine = machineNo;
                            break;
                        }
                    }
                    
                    if (hasDuplicate) break;
                    machines.push(machineNo);
                }
            }
            
            if (hasDuplicate) {
                if (isOngoingLot) {
                    alert('Duplicate machine number detected: ' + duplicateMachine + '\nThis machine is already assigned to this ongoing lot.');
                } else {
                    alert('Duplicate machine number detected: ' + duplicateMachine + '\nPlease use different machines for each entry.');
                }
                return false;
            }
            
            // Check if total equipment exceeds 10 for ongoing lots
            if (isOngoingLot) {
                var totalEquipment = ongoingEquipment.length;
                for (var i = 0; i < equipmentCount; i++) {
                    var eqpField = document.getElementById('eqp_no_' + i);
                    if (eqpField && eqpField.value) {
                        totalEquipment++;
                    }
                }
                
                if (totalEquipment > 10) {
                    alert('Cannot add more equipment. Maximum 10 machines per lot.\nCurrent: ' + ongoingEquipment.length + ' machines\nAdding: ' + (totalEquipment - ongoingEquipment.length) + ' machines\nTotal would be: ' + totalEquipment);
                    return false;
                }
            }
            
            return true;
        }
        
        function clearForm() {
            if (confirm('Clear all form data?')) {
                document.forms[0].reset();
                document.getElementById('employee_name').innerHTML = '';
                document.getElementById('lot_error').innerHTML = '';
                document.getElementById('employee_error').innerHTML = '';
                for (var i = 0; i < equipmentCount; i++) {
                    var errorDiv = document.getElementById('eqp_error_' + i);
                    if (errorDiv) errorDiv.innerHTML = '';
                    var capaField = document.getElementById('eqp_capa_' + i);
                    if (capaField) capaField.value = '';
                }
                
                // Reset ongoing state
                isOngoingLot = false;
                ongoingEquipment = [];
                document.getElementById('ongoing_section').style.display = 'none';
                document.getElementById('machine_section_title').innerHTML = 'MACHINE ASSIGNMENT (Max 3 machines)';
                
                // Reset submit button text
                var submitBtn = document.getElementById('submit_btn');
                if (submitBtn) {
                    submitBtn.value = 'SAVE LOT ENTRY';
                }
                
                // Reload fixed machines if enabled
                var fixedEnabled = document.getElementById('fixed_machines').checked;
                if (fixedEnabled) {
                    loadFixedMachines();
                }
            }
        }
    </script>
</body>
</html>
