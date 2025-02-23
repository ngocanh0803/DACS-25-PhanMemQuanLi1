$(document).ready(function(){
    $('#menu-search').on('input', function(){
        var query = $(this).val().trim();
        if(query.length === 0){
            $('#search-results').hide();
            return;
        }
        $.ajax({
            url: 'search_menu.php',
            type: 'GET',
            dataType: 'json',
            data: { query: query },
            success: function(response){
                var results = response.results;
                if(results.length > 0){
                    var html = '<ul>';
                    results.forEach(function(item){
                        html += '<li data-url="'+ item.url +'"><i class="fas '+ item.icon +'"></i>' + item.name + '</li>';
                    });
                    html += '</ul>';
                    $('#search-results').html(html).show();
                } else {
                    $('#search-results').html('<p style="padding:10px;">Không tìm thấy kết quả nào.</p>').show();
                }
            },
            error: function(){
                $('#search-results').html('<p style="padding:10px;">Đã xảy ra lỗi. Vui lòng thử lại.</p>').show();
            }
        });
    });

    // Xử lý khi người dùng nhấp vào một kết quả tìm kiếm
    $(document).on('click', '#search-results li', function(){
        var url = $(this).data('url');
        window.location.href = url;
    });

    // Ẩn kết quả khi nhấp bên ngoài
    $(document).click(function(event) { 
        var $target = $(event.target);
        if(!$target.closest('.search-box').length && 
           !$target.closest('#search-results').length) {
            $('#search-results').hide();
        }        
    });
});

