document.addEventListener('DOMContentLoaded', function() {
    // Toggle Sidebar
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('hidden');
    });

    // Toggle Submenu
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        const title = item.querySelector('.menu-title');
        
        title.addEventListener('click', function() {
            // Close other open menus
            menuItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current menu
            item.classList.toggle('active');
        });
    });

    // Submenu item click handler
    const submenuItems = document.querySelectorAll('.submenu li');
    
    submenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event bubbling
            
            // Remove active class from all submenu items
            submenuItems.forEach(subItem => {
                subItem.classList.remove('active');
            });
            
            // Add active class to clicked item
            item.classList.add('active');
            
            // Here you can add logic to load different content based on menu selection
            console.log('Selected menu item:', item.textContent);
        });
    });

    // Add hover effect to buttons
    const buttons = document.querySelectorAll('button');
    
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// Thêm vào phần JavaScript hiện có

// Hàm tạo phòng
function createRoom(roomNumber, capacity, status) {
    const room = document.createElement('div');
    room.className = `room ${status}`;
    room.innerHTML = `
        <div class="room-number">Phòng ${roomNumber}</div>
        <div class="room-capacity">${capacity} người</div>
    `;
    room.addEventListener('click', () => showRoomDetails(roomNumber, capacity, status));
    return room;
}

// Hàm hiển thị chi tiết phòng
function showRoomDetails(roomNumber, capacity, status) {
    alert(`
        Phòng: ${roomNumber}
        Sức chứa: ${capacity} người
        Trạng thái: ${status}
    `);
}

// Hàm tạo sơ đồ phòng
function createFloorPlan(building, floor) {
    const roomsContainer = document.querySelector('.rooms-container');
    roomsContainer.innerHTML = '';

    // Giả lập dữ liệu phòng (trong thực tế sẽ lấy từ database)
    const roomsPerFloor = 10;
    for (let i = 1; i <= roomsPerFloor; i++) {
        const roomNumber = `${building}${floor}${i.toString().padStart(2, '0')}`;
        const capacity = 4;
        const status = ['available', 'occupied', 'maintenance'][Math.floor(Math.random() * 3)];
        const room = createRoom(roomNumber, capacity, status);
        roomsContainer.appendChild(room);
    }
}

// Xử lý chuyển đổi content khi click vào menu
document.querySelectorAll('.submenu li').forEach(item => {
    item.addEventListener('click', function() {
        const menuText = this.textContent.trim();
        const mainContent = document.querySelector('.content');

        if (menuText === 'Xem sơ đồ') {
            // Lấy template và clone nó
            const template = document.getElementById('floor-plan-template');
            const floorPlanContent = template.content.cloneNode(true);
            
            // Xóa nội dung hiện tại và thêm sơ đồ phòng
            mainContent.innerHTML = '';
            mainContent.appendChild(floorPlanContent);

            // Lấy các elements sau khi đã thêm vào DOM
            const buildingSelect = document.getElementById('building-select');
            const floorSelect = document.getElementById('floor-select');
            const currentBuilding = document.getElementById('current-building');
            const currentFloor = document.getElementById('current-floor');

            // Xử lý sự kiện khi chọn tòa nhà
            buildingSelect.addEventListener('change', function() {
                if (this.value && floorSelect.value) {
                    currentBuilding.textContent = this.value;
                    createFloorPlan(this.value, floorSelect.value);
                }
            });

            // Xử lý sự kiện khi chọn tầng
            floorSelect.addEventListener('change', function() {
                if (this.value && buildingSelect.value) {
                    currentFloor.textContent = this.value;
                    createFloorPlan(buildingSelect.value, this.value);
                }
            });

            // Khởi tạo sơ đồ mặc định
            createFloorPlan('A', '1');
        }
    });
});

// Thêm class Room để quản lý thông tin phòng
class Room {
    constructor(building, floor, number, capacity, status) {
        this.building = building;
        this.floor = floor;
        this.number = number;
        this.capacity = capacity;
        this.status = status;
        this.currentOccupants = 0;
        this.facilities = [];
    }

    // Thêm tiện nghi vào phòng
    addFacility(facility) {
        this.facilities.push(facility);
    }

    // Cập nhật trạng thái phòng
    updateStatus(newStatus) {
        this.status = newStatus;
    }

    // Thêm sinh viên vào phòng
    addStudent() {
        if (this.currentOccupants < this.capacity) {
            this.currentOccupants++;
            if (this.currentOccupants === this.capacity) {
                this.status = 'occupied';
            }
            return true;
        }
        return false;
    }

    // Xóa sinh viên khỏi phòng
    removeStudent() {
        if (this.currentOccupants > 0) {
            this.currentOccupants--;
            if (this.currentOccupants < this.capacity) {
                this.status = 'available';
            }
            return true;
        }
        return false;
    }
}

// Thêm class RoomManager để quản lý tất cả các phòng
class RoomManager {
    constructor() {
        this.rooms = new Map();
    }

    // Thêm phòng mới
    addRoom(room) {
        const roomKey = `${room.building}${room.floor}${room.number}`;
        this.rooms.set(roomKey, room);
    }

    // Lấy thông tin phòng
    getRoom(building, floor, number) {
        const roomKey = `${building}${floor}${number}`;
        return this.rooms.get(roomKey);
    }

    // Lấy tất cả phòng theo tầng
    getRoomsByFloor(building, floor) {
        return Array.from(this.rooms.values())
            .filter(room => room.building === building && room.floor === floor);
    }

    // Lấy tất cả phòng trống
    getAvailableRooms() {
        return Array.from(this.rooms.values())
            .filter(room => room.status === 'available');
    }
}

// Khởi tạo dữ liệu mẫu
function initializeSampleData() {
    const roomManager = new RoomManager();
    
    // Tạo dữ liệu mẫu cho các tòa nhà
    ['A', 'B', 'C'].forEach(building => {
        for (let floor = 1; floor <= 5; floor++) {
            for (let room = 1; room <= 10; room++) {
                const roomNumber = room.toString().padStart(2, '0');
                const capacity = 4;
                const status = ['available', 'occupied', 'maintenance'][Math.floor(Math.random() * 3)];
                
                const newRoom = new Room(building, floor, roomNumber, capacity, status);
                
                // Thêm một số tiện nghi mẫu
                newRoom.addFacility('Điều hòa');
                newRoom.addFacility('Tủ quần áo');
                newRoom.addFacility('Bàn học');
                
                roomManager.addRoom(newRoom);
            }
        }
    });

    return roomManager;
}

// Khởi tạo RoomManager với dữ liệu mẫu
const roomManager = initializeSampleData();

// Hàm hiển thị chi tiết phòng (cập nhật)
function showRoomDetails(roomNumber, capacity, status) {
    const building = roomNumber.charAt(0);
    const floor = roomNumber.charAt(1);
    const number = roomNumber.slice(2);
    
    const room = roomManager.getRoom(building, floor, number);
    
    if (room) {
        const facilitiesList = room.facilities.join(', ');
        const detailsHTML = `
            <div class="room-details-popup">
                <h3>Chi tiết phòng ${roomNumber}</h3>
                <p>Sức chứa: ${room.capacity} người</p>
                <p>Đang ở: ${room.currentOccupants} người</p>
                <p>Trạng thái: ${room.status}</p>
                <p>Tiện nghi: ${facilitiesList}</p>
                <button onclick="this.parentElement.remove()">Đóng</button>
            </div>
        `;

        const popup = document.createElement('div');
        popup.className = 'popup-overlay';
        popup.innerHTML = detailsHTML;
        document.body.appendChild(popup);
    }
}
