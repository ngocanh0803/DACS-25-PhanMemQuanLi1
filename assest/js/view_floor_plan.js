document.addEventListener("DOMContentLoaded", function () {
    const buildingSelect = document.getElementById("building-select");
    const floorSelect = document.getElementById("floor-select");
    const roomsContainer = document.querySelector(".rooms-container");
    const currentBuilding = document.getElementById("current-building");
    const currentFloor = document.getElementById("current-floor");

    const roomPopup = document.getElementById("room-popup");
    const popupRoomNumber = document.getElementById("popup-room-number");
    const popupRoomCapacity = document.getElementById("popup-room-capacity");
    const popupRoomStatus = document.getElementById("popup-room-status");
    const closeBtn = document.querySelector(".close-btn");

    loadRooms("A", "1");

    // Gán sự kiện cho dropdown để cập nhật danh sách phòng khi thay đổi tòa nhà
    buildingSelect.addEventListener("change", function() {
        const building = buildingSelect.value;
        const floor = floorSelect.value;
        loadRooms(building, floor);
    });

    // Gán sự kiện cho dropdown để cập nhật danh sách phòng khi thay đổi tầng
    floorSelect.addEventListener("change", function() {
        const building = buildingSelect.value;
        const floor = floorSelect.value;
        loadRooms(building, floor);
    });

    function loadRooms(building, floor) {
        fetch(`fetch_rooms.php?building=${building}&floor=${floor}`)
            .then(response => {
                if (!response.ok) throw new Error("Network response was not ok");
                return response.json();
            })
            .then(data => {
                roomsContainer.innerHTML = ""; 
                currentBuilding.textContent = building;
                currentFloor.textContent = floor;

                data.forEach(room => {
                    const roomDiv = document.createElement("div");
                    roomDiv.classList.add("room");

                    // Gán room_id thực tế vào thuộc tính data-room-id
                    roomDiv.setAttribute("data-room-id", room.room_id);

                    if (room.status === "available") {
                        roomDiv.classList.add("available");
                    } else if (room.status === "occupied") {
                        roomDiv.classList.add("occupied");
                    } else if (room.status === "maintenance") {
                        roomDiv.classList.add("maintenance");
                    }

                    roomDiv.innerHTML = `
                        <div class="room-number">Phòng ${room.room_number}</div>
                        <div class="room-capacity">${room.capacity} người</div>
                    `;

                    roomDiv.addEventListener("click", () => {
                        showPopup(room);
                    });

                    roomsContainer.appendChild(roomDiv);
                });
            })
            .catch(error => console.error("Error loading rooms:", error));
    }

    function showPopup(room) {
        popupRoomNumber.textContent = room.room_number;
        popupRoomNumber.setAttribute("data-room-id", room.room_id); // Lưu room_id thực sự vào thuộc tính
        popupRoomCapacity.textContent = room.capacity;
        popupRoomStatus.textContent = room.status === "available" ? "Trống" : room.status === "occupied" ? "Đã có người ở" : "Đang sửa chữa";
        
        // Thêm class active để hiển thị popup
        roomPopup.classList.add("active");
    }

    closeBtn.addEventListener("click", () => {
        roomPopup.classList.remove("active");
    });


    // Đóng popup khi click ra ngoài vùng popup
    window.addEventListener("click", function(event) {
        if (event.target === roomPopup) {
            roomPopup.classList.remove("active");
        }
        if (event.target === studentPopup) {
            studentPopup.classList.remove("active");
        }
    });

    document.getElementById("view-details-btn").addEventListener("click", function() {
        // Lấy room_id thực tế từ thuộc tính data-room-id
        const roomId = document.getElementById("popup-room-number").getAttribute("data-room-id");
        console.log("room_id được gửi:", roomId); // Kiểm tra room_id
    
        fetch(`fetch_students.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(data => {
                const studentList = document.getElementById("student-list");
                studentList.innerHTML = "";
    
                data.forEach(student => {
                    const studentItem = document.createElement("p");
                    studentItem.textContent = `Tên sinh viên: ${student.full_name}, Mã SV: ${student.student_code}`;
                    studentList.appendChild(studentItem);
                });
    
                document.getElementById("student-popup").classList.add("active");
            })
            .catch(error => console.error("Lỗi khi tải danh sách sinh viên:", error));
    });
    
    
    // Đóng popup chi tiết sinh viên
    document.querySelector(".close-btn-student").addEventListener("click", () => {
        document.getElementById("student-popup").classList.remove("active");
    });
    
});
