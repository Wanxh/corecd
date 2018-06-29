/**
 * Created by linyang on 2018/5/29.
 */
/**
 * 系统设置保存
 */
function onSave() {
    var form = $('#setting-form');
    var data = form.serialize();
    console.log(data);
    for (var key in data) {
        if (!data[key]) {
            alert(key + ' 不能为空!');
            return;
        }
    }
    $.ajax({
        url: form.attr('action'),
        data: data,
        dataType: 'json',
        type: 'post',
        success: function (data) {
            if (!data.code) {
                alert('修改成功');
                window.location.reload();
            } else {
                alert(data.msg);
            }
        }
    });
}