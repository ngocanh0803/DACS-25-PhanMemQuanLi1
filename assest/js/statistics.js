document.addEventListener('DOMContentLoaded', function() {
    fetch('statistics.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-students').textContent = data.total_students;

            document.getElementById('rooms-occupied').textContent = data.room_status.occupied;
            document.getElementById('rooms-maintenance').textContent = data.room_status.maintenance;
            document.getElementById('rooms-available').textContent = data.room_status.available;

            const occupancyContainer = document.getElementById('occupancy-rates');
            occupancyContainer.innerHTML = '';
            data.capacity_occupancy.forEach(item => {
                const occupancyItem = document.createElement('div');
                occupancyItem.classList.add('occupancy-item');
                occupancyItem.innerHTML = `
                    <h3>${item.capacity}-Person Rooms</h3>
                    <p>Occupancy Rate: ${item.occupancy_rate}%</p>
                `;
                occupancyContainer.appendChild(occupancyItem);
            });
        })
        .catch(error => console.error('Error fetching statistics:', error));
});
