/**
 * Created by linyang on 2018/5/29.
 */
/**
 * 用户登录
 */
$('.auth-box.right').hide();

//键盘回车
$('#input-totp').keyup(function (event) {
    if (event.keyCode == 13) {
        onLogin();
    }
});

//登录操作
function onLogin() {
    var username = $('#input-username').val();
    var totp = $('#input-totp').val();
    if (!username || !totp) {
        showError('用户名或验证码必填!');
        return;
    }

    $.post(
        '/login/in',
        {username: username, totp: totp},
        function (data) {
            showMsgJump(data)
        },
        'json'
    ).error(function () {
        showMsgJump(null, '服务器连接异常')
    });
}