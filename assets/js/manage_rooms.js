document.addEventListener("DOMContentLoaded", function () {
    const buildingSelect = document.getElementById("building-select");
    const floorSelect = document.getElementById("floor-select");
    const loadRoomsButton = document.getElementById("load-rooms");
    const roomsContainer = document.querySelector(".rooms-container");
    const currentBuilding = document.getElementById("current-building");
    const currentFloor = document.getElementById("current-floor");
    const notification = document.getElementById("notification");

    // Hàm hiển thị thông báo
    function showNotification(message, type) {
        notification.textContent = message;
        notification.className = `notification show ${type}`; // Ví dụ: 'notification show success'

        // Ẩn thông báo sau 3 giây
        setTimeout(() => {
            notification.classList.remove("show", type);
        }, 3000);
    }

    loadRoomsButton.addEventListener("click", () => {
        loadRooms(buildingSelect.value, floorSelect.value);
    });

    // Tải phòng mặc định khi trang được tải lần đầu
    loadRooms('A', '1');

    function loadRooms(building, floor) {
        if (!building || !floor) {
            showNotification("Vui lòng chọn tòa nhà và tầng trước khi tải phòng.", "error");
            return;
        }

        loadRoomsButton.classList.add("loading"); // Thêm lớp loading vào nút

        fetch(`fetch_rooms.php?building=${encodeURIComponent(building)}&floor=${encodeURIComponent(floor)}`)
            .then(response => response.json())
            .then(data => {
                roomsContainer.innerHTML = ""; // Xóa danh sách phòng cũ
                currentBuilding.textContent = building;
                currentFloor.textContent = floor;

                if (data.length === 0) {
                    roomsContainer.innerHTML = "<p>Không có phòng nào để hiển thị.</p>";
                    return;
                }

                data.forEach(room => {
                    const roomDiv = document.createElement("div");
                    roomDiv.classList.add("room");

                    // Thêm lớp trạng thái dựa trên trạng thái phòng
                    if (room.status === "available") {
                        roomDiv.classList.add("available");
                    } else if (room.status === "occupied") {
                        roomDiv.classList.add("occupied");
                    } else if (room.status === "maintenance") {
                        roomDiv.classList.add("maintenance");
                    }

                    // Thêm thuộc tính data-room-id để lưu room_id thực
                    roomDiv.setAttribute("data-room-id", room.room_id);

                    // roomDiv.innerHTML = `
                    //     <div class="room-info">
                    //         <span>Phòng ${room.room_number} - Trạng thái: ${translateStatus(room.status)}</span>
                    //         <select class="status-select" data-room-id="${room.room_id}">
                    //             <option value="available" ${room.status === "available" ? "selected" : ""}>Trống</option>
                    //             <option value="occupied" ${room.status === "occupied" ? "selected" : ""}>Đang ở</option>
                    //             <option value="maintenance" ${room.status === "maintenance" ? "selected" : ""}>Sửa chữa</option>
                    //         </select>
                    //         <button class="update-status">Cập nhật</button>
                    //     </div>
                    // `;

                    roomDiv.innerHTML = `
                        <div class="room-info">
                            <span>Phòng ${room.room_number} - Trạng thái: ${translateStatus(room.status)}</span>
                            <select class="status-select" data-room-id="${room.room_id}">
                                <option value="available" ${room.status === "available" ? "selected" : ""}>Trống</option>
                                <option value="occupied" ${room.status === "occupied" ? "selected" : ""}>Đang ở</option>
                                <option value="maintenance" ${room.status === "maintenance" ? "selected" : ""}>Sửa chữa</option>
                            </select>
                            <button class="update-status">Cập nhật</button>
                        </div>
                    `;
                    roomsContainer.appendChild(roomDiv);

                    // Thêm sự kiện click cho nút "Cập nhật" của từng phòng
                    const updateButton = roomDiv.querySelector(".update-status");
                    const statusSelect = roomDiv.querySelector(".status-select");

                    updateButton.addEventListener("click", () => {
                        const newStatus = statusSelect.value;
                        const roomId = statusSelect.getAttribute("data-room-id");
                        updateRoomStatus(roomId, newStatus);
                    });
                });
            })
            .catch(error => {
                console.error("Lỗi khi tải danh sách phòng:", error);
                showNotification("Đã xảy ra lỗi khi tải danh sách phòng.", "error");
            })
            .finally(() => {
                loadRoomsButton.classList.remove("loading"); // Bỏ lớp loading sau khi hoàn thành
            });
    }

    // Hàm dịch trạng thái từ tiếng Anh sang tiếng Việt
    function translateStatus(status) {
        switch (status) {
            case "available":
                return "Trống";
            case "occupied":
                return "Đang ở";
            case "maintenance":
                return "Sửa chữa";
            default:
                return status;
        }
    }

    // Hàm gửi yêu cầu cập nhật trạng thái phòng
    function updateRoomStatus(roomId, newStatus) {
        // Kiểm tra xem trạng thái có thay đổi hay không
        const roomDiv = document.querySelector(`.room[data-room-id="${roomId}"]`);
        const currentStatus = roomDiv.classList.contains("available") ? "available"
                            : roomDiv.classList.contains("occupied") ? "occupied"
                            : "maintenance";
        if (currentStatus === newStatus) {
            showNotification("Trạng thái phòng chưa thay đổi.", "error");
            return;
        }

        fetch('update_room_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ room_id: roomId, status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success'); // Hiển thị thông báo thành công

                // Cập nhật lớp trạng thái của phòng
                roomDiv.classList.remove('available', 'occupied', 'maintenance');
                roomDiv.classList.add(newStatus);

                // Cập nhật văn bản trạng thái
                const statusText = roomDiv.querySelector(".room-info span");
                statusText.textContent = `Phòng ${statusText.textContent.split(' - ')[0].split(' ')[1]} - Trạng thái: ${translateStatus(newStatus)}`;
            } else {
                showNotification(data.message, 'error'); // Hiển thị thông báo lỗi
            }
        })
        .catch(error => {
            console.error("Lỗi khi cập nhật trạng thái:", error);
            showNotification("Đã xảy ra lỗi khi cập nhật trạng thái phòng.", 'error');
        });
    }
});
