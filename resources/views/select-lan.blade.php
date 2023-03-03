<li class="dropdown notifications-menu" id="select-lan">
    <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-language"></i>
        <?php
            $lan = \App\Admin\Controllers\CommonController::lan();
            echo "<b>$lan->name - $lan->short_name</b>";
        ?>
    </a>
    <ul class="dropdown-menu">
        <li>
            <ul class="menu">

            </ul>
        </li>
    </ul>
</li>
<script>
    $('#select-lan .dropdown-toggle').click(function () {
        $.ajax({
            'url':'{{rtrim(config('app.url'),'/').'/admin/language-getList'}}',
            'method':'get',
            data: {
                _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            success:function (data) {
                let list = data.list;
                let checked = data.checked;
                let html = '';
                for(let d of list){
                    if(checked === d.id){
                        html+= `<li style="background: #e6e6e6"><a class="select-lan-btn" data-id='${d.id}' href="javascript:void(0);">${d.name} - ${d.short_name}</a></li>`;
                        continue;
                    }
                    html+= `<li><a class="select-lan-btn" data-id='${d.id}' href="javascript:void(0);">${d.name} - ${d.short_name}</a></li>`;
                }
                document.getElementById('select-lan').querySelector('ul.menu').innerHTML = html;

                $('#select-lan .select-lan-btn').click(function () {
                    $.ajax({
                        'url':'{{rtrim(config('app.url'),'/').'/admin/language-selectList'}}',
                        'method':'post',
                        data: {
                            _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            id:$(this).attr('data-id')
                        },
                        success:function (d) {
                            window.location.reload();
                        }
                    })
                });
            }
        })
    });
</script>
