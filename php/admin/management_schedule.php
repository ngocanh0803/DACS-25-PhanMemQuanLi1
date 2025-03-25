<?php
include '../config/db_connect.php';

// Xử lý Thêm/Sửa/Xóa (AJAX)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? ''; // 'add', 'edit', hoặc 'delete'

    if ($action == 'add' || $action == 'edit') {
        $id = $_POST['id'] ?? null; // Chỉ có khi sửa
        $duty_date = $_POST['duty_date']; // Sửa tên biến
        $shift = $_POST['shift'];        // Sửa tên biến
        $staff_name = $_POST['staff_name'];  // Sửa tên biến
        $position = $_POST['position'];    // Sửa tên biến
        $note = $_POST['note'];          // Sửa tên biến

        // Tính thứ (nếu bạn muốn lưu cả thứ)
        $duty_day = date('l', strtotime($duty_date));
        $days_vn = [  // Bạn có thể bỏ phần này nếu chỉ lưu 'duty_day' bằng tiếng Anh
            'Monday' => 'Thứ Hai', 'Tuesday' => 'Thứ Ba', 'Wednesday' => 'Thứ Tư',
            'Thursday' => 'Thứ Năm', 'Friday' => 'Thứ Sáu', 'Saturday' => 'Thứ Bảy', 'Sunday' => 'Chủ Nhật'
        ];
        $duty_day = $days_vn[$duty_day]; // Đổi tên biến cho đồng bộ


        if ($action == 'add') {
            $sql = "INSERT INTO duty_schedule (duty_date, duty_day, shift, staff_name, position, note) VALUES (?, ?, ?, ?, ?, ?)"; // Sửa tên cột
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement (add): ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("ssssss", $duty_date, $duty_day, $shift, $staff_name, $position, $note); // Sửa tên biến

        } else { // edit
            $sql = "UPDATE duty_schedule SET duty_date = ?, duty_day = ?, shift = ?, staff_name = ?, position = ?, note = ? WHERE id = ?"; // Sửa tên cột
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement (edit): ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("ssssssi", $duty_date, $duty_day, $shift, $staff_name, $position, $note, $id); // Sửa tên biến
        }

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            echo json_encode(['success' => true, 'id' => $newId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi execute: ' . $stmt->error]);
        }

        $stmt->close();
        exit();

    } elseif ($action == 'delete') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $sql = "DELETE FROM duty_schedule WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                 echo json_encode(['success' => false, 'message' => 'Lỗi prepare statement (delete): ' . $conn->error]);
                exit();
            }

            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi execute (delete): ' . $stmt->error]);
            }
            $stmt->close();

        } else {
            echo json_encode(['success' => false, 'message' => 'Không có ID']);
        }
        exit();
    }
}

// Lấy dữ liệu từ database (để hiển thị)
$sql = "SELECT * FROM duty_schedule ORDER BY duty_date, shift"; // Sửa 'ngay' và 'ca_truc'
$result = $conn->query($sql);
$schedules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}
$conn->close();
?>

<!-- Phần nội dung (không có <html>, <head>, <body>) -->
<div class="header">
    <p>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
    <p class="doc-lap">Độc lập – Tự do – Hạnh phúc</p>
    <hr width="30%">
</div>
<h1>LỊCH PHÂN CÔNG TRỰC</h1>
<h2>KÝ TÚC XÁ \[TÊN KÝ TÚC XÁ]</h2>
<h3 class="title-time">Tháng ... Năm ...</h3>

<p>Ban Quản lý Ký túc xá \[Tên Ký túc xá] thông báo lịch phân công trực cho cán bộ quản lý như sau:</p>

<table>
    <thead>
        <tr>
            <th>Ngày</th>
            <th>Thứ</th>
            <th>Ca trực</th>
            <th>Cán bộ trực</th>
            <th>Chức vụ</th>
            <th>Ghi chú</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($schedules as $schedule):
        // Chuyển đổi duty_day sang tiếng Việt để hiển thị
        $days_vn = [
            'Monday' => 'Thứ Hai',
            'Tuesday' => 'Thứ Ba',
            'Wednesday' => 'Thứ Tư',
            'Thursday' => 'Thứ Năm',
            'Friday' => 'Thứ Sáu',
            'Saturday' => 'Thứ Bảy',
            'Sunday' => 'Chủ Nhật'
        ];
        $display_day = $days_vn[$schedule['duty_day']] ?? $schedule['duty_day']; // Hiển thị tiếng Việt, nếu có
        ?>

        <tr id="row-<?php echo $schedule['id']; ?>">
            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($schedule['duty_date']))); ?></td>
            <td><?php echo htmlspecialchars($display_day); ?></td>
            <td><?php echo htmlspecialchars($schedule['shift']); ?></td>
            <td><?php echo htmlspecialchars($schedule['staff_name']); ?></td>
            <td><?php echo htmlspecialchars($schedule['position']); ?></td>
            <td><?php echo htmlspecialchars($schedule['note']); ?></td>
            <td class="action-buttons">
                <button class="edit-button" onclick="openEditModal(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars(date('Y-m-d', strtotime($schedule['duty_date']))); ?>', '<?php echo htmlspecialchars($schedule['shift']); ?>', '<?php echo htmlspecialchars($schedule['staff_name']); ?>', '<?php echo htmlspecialchars($schedule['position']); ?>', '<?php echo htmlspecialchars($schedule['note']); ?>')">Sửa</button>
                <button class="delete-button" onclick="confirmDelete(<?php echo $schedule['id']; ?>)">Xóa</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<button class="read-button" onclick="openAddModal()">Thêm Lịch Trực</button>

<!-- Modal Thêm/Sửa -->
<div id="addEditModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddEditModal()">×</span>
        <h2 id="modalTitle">Thêm Lịch Trực</h2>
        <form id="addEditForm">
            <input type="hidden" id="action" name="action" value="add">
            <input type="hidden" id="editId" name="id" value="">

            <label for="duty_date">Ngày:</label>  <!-- Sửa -->
            <input type="date" id="duty_date" name="duty_date" required><br><br> <!-- Sửa -->

            <label for="shift">Ca trực:</label> <!-- Sửa -->
            <select id="shift" name="shift" required> <!-- Sửa -->
                <option value="Sáng (7:00 - 11:30)">Sáng (7:00 - 11:30)</option>
                <option value="Chiều (13:30 - 17:00)">Chiều (13:30 - 17:00)</option>
                <option value="Tối (17:00 - 22:00)">Tối (17:00 - 22:00)</option>
            </select><br><br>

            <label for="staff_name">Cán bộ trực:</label> <!-- Sửa -->
            <input type="text" id="staff_name" name="staff_name" required><br><br> <!-- Sửa -->

            <label for="position">Chức vụ:</label> <!-- Sửa -->
            <input type="text" id="position" name="position"><br><br> <!-- Sửa -->

            <label for="note">Ghi chú:</label> <!-- Sửa -->
            <textarea id="note" name="note"></textarea><br><br> <!-- Sửa -->

            <div class="modal-buttons">
                <button type="button" id="addUpdateButton">Thêm</button>
                <button type="button" class="cancel-button" onclick="closeAddEditModal()">Hủy</button>
            </div>

        </form>
    </div>
</div>
<!-- Modal xác nhận xóa -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">×</span>
        <p>Bạn có chắc chắn muốn xóa lịch trực này?</p>
            <div class="modal-buttons">
            <button id="confirmDeleteBtn">Xóa</button>
            <button class="cancel-button" onclick="closeModal()">Hủy</button>
        </div>
    </div>
</div>
<script>
    let deleteId;

    function confirmDelete(id) {
        deleteId = id;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    // Hàm mở modal Thêm
    function openAddModal() {
        document.getElementById('addEditModal').style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Thêm Lịch Trực';
        document.getElementById('action').value = 'add';
        document.getElementById('addUpdateButton').innerText = 'Thêm'; // Đặt lại nút

        // Reset form
        document.getElementById('addEditForm').reset();
    }
    // Hàm mở modal Sửa
    // Sửa lại các tên biến trong openEditModal
    function openEditModal(id, duty_date, shift, staff_name, position, note) {
        document.getElementById('addEditModal').style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Sửa Lịch Trực';
        document.getElementById('action').value = 'edit';
        document.getElementById('addUpdateButton').innerText = 'Cập Nhật';

        // Điền dữ liệu vào form
        document.getElementById('editId').value = id;
        document.getElementById('duty_date').value = duty_date;
        document.getElementById('shift').value = shift;
        document.getElementById('staff_name').value = staff_name;
        document.getElementById('position').value = position;
        document.getElementById('note').value = note;
    }


    // Hàm đóng modal Thêm/Sửa
    function closeAddEditModal() {
        document.getElementById('addEditModal').style.display = 'none';
    }
    // Xử lý Thêm/Sửa (AJAX)
    document.getElementById('addUpdateButton').addEventListener('click', function() {
        const action = document.getElementById('action').value;
        const id = document.getElementById('editId').value;
        const duty_date = document.getElementById('duty_date').value; // Sửa
        const shift = document.getElementById('shift').value; //Sửa
        const staff_name = document.getElementById('staff_name').value;// Sửa
        const position = document.getElementById('position').value;//Sửa
        const note = document.getElementById('note').value;//Sửa

        const data = {
            action: action,
            duty_date: duty_date, // Sửa
            shift: shift, // Sửa
            staff_name: staff_name, // Sửa
            position: position, // Sửa
            note: note // Sửa
        };
        if (action == 'edit') {
            data.id = id;
        }

        fetch('management_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(action === 'add' ? 'Thêm thành công!' : 'Cập nhật thành công!');

                // Thêm hoặc cập nhật dòng vào bảng
                if (action === 'add') {
                    const newRow = `
                        <tr id="row-${data.id}">
                            <td>${formatDate(duty_date)}</td>
                            <td>${getThu(duty_date)}</td>
                            <td>${shift}</td>
                            <td>${staff_name}</td>
                            <td>${position}</td>
                            <td>${note}</td>
                            <td class="action-buttons">
                                <button class="edit-button" onclick="openEditModal(${data.id}, '${duty_date}', '${shift}', '${staff_name}', '${position}', '${note}')">Sửa</button>
                                <button class="delete-button" onclick="confirmDelete(${data.id})">Xóa</button>
                            </td>
                        </tr>`;
                    document.querySelector('tbody').insertAdjacentHTML('beforeend', newRow);

                } else { // edit
                    const row = document.getElementById('row-' + id);
                    if (row) {
                        row.querySelector('td:nth-child(1)').innerText = formatDate(duty_date);
                        row.querySelector('td:nth-child(2)').innerText = getThu(duty_date);
                        row.querySelector('td:nth-child(3)').innerText = shift;
                        row.querySelector('td:nth-child(4)').innerText = staff_name;
                        row.querySelector('td:nth-child(5)').innerText = position;
                        row.querySelector('td:nth-child(6)').innerText = note;
                        row.querySelector('.edit-button').setAttribute('onclick', `openEditModal(${id}, '${duty_date}', '${shift}', '${staff_name}', '${position}', '${note}')`);
                    }
                }

                closeAddEditModal();

            } else {
                alert('Lỗi: ' + data.message);
                console.log(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra!');
        });
    });
     //Xử lí xóa
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {

        // Gửi yêu cầu AJAX để xóa
        fetch('management_schedule.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body:  new URLSearchParams({action: 'delete', id: deleteId}).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Xóa thành công!');
                // Xóa dòng khỏi bảng
                document.getElementById('row-' + deleteId).remove();
                closeModal();
            } else {
                alert('Lỗi khi xóa: ' + data.message);
            }
        })
        .catch(error => {
                console.error('Error:', error);
            alert('Có lỗi xảy ra khi xóa!');
        });
    });
    //Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()}`;
    }

    // Get day of week
    function getThu(dateString) {
        const date = new Date(dateString);
        const dayOfWeek = date.getDay(); // 0 (Chủ Nhật) đến 6 (Thứ Bảy)
        const days = ["Chủ Nhật", "Thứ Hai", "Thứ Ba", "Thứ Tư", "Thứ Năm", "Thứ Sáu", "Thứ Bảy"];
        return days[dayOfWeek];
    }
</script>