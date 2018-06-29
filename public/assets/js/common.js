/**
 * Created by linyang on 2017/12/19.
 */
function displayError(value) {
    window.alert(value)
}


/**
 * 判断是否请求成功
 *
 * @param data 接口返回数据
 * @returns {boolean}
 */
function isDataSuccess(data) {
    if (data && data.hasOwnProperty("code")) {
        return data.code == '0'
    }
    return false
}

/**
 * 延时1秒跳转到某个地址(为了看到弹出的提示)
 *
 * @param url 要跳转的地址
 * @param delaySecond 延时时间(默认1秒)
 */
function jumpUrl(url, delaySecond) {
    if (delaySecond == undefined) {
        delaySecond = 1;
    }
    if (url == undefined) {
        url = window.location.href
    }
    setTimeout("window.location.href='" + url + "'", 1000 * delaySecond);
}

/**
 * 显示接口的成功信息,传了defaultMsg如果msg不存在展示defaultMsg
 *
 * @param data
 * @param defaultMsg
 */
function showDataSuccess(data, defaultMsg) {
    var msg = '';
    if (data.msg != undefined) {
        msg = data.msg;
    } else if (defaultMsg != undefined) {
        msg = defaultMsg
    } else {
        msg = "服务器异常"
    }
    showSuccess(msg)
}

/**
 * 显示接口的失败信息,传了defaultMsg如果msg不存在展示defaultMsg
 *
 * @param data
 * @param defaultMsg
 */
function showDataError(data, defaultMsg) {
    var msg = '';
    if (data && data.hasOwnProperty('msg')) {
        msg = data.msg;
    } else if (defaultMsg != undefined) {
        msg = defaultMsg
    } else {
        msg = '服务器异常'
    }
    showError(msg)
}


/**
 * 弹出成功
 */
function showSuccess(msg) {
    layer.msg(msg, {
        icon: 1,
    });
}

/**
 * 弹出失败
 * @param msg
 */
function showError(msg) {
    layer.msg(msg, {
        icon: 5,
    });
}


/**
 * 设置模态框的值
 *
 * @param prefix 表单名字前缀
 * @param dataObj 存放字段的对象
 * @param attributes 设置的对象属性数组
 */
function setInputValue(prefix, dataObj, attributes) {
    for (var i = 0; i < attributes.length; i++) {
        var attribute = attributes[i];
        var inputId = prefix + ucfirst(attribute);
        $("#" + inputId).val(dataObj.data(attribute))
    }
}

/**
 * 获取模态框的form参数值
 *
 * @param prefix 表单名字前缀
 * @param attributes 获取的对象属性数组
 * @returns {{}}
 */
function getInputParams(prefix, attributes) {
    var params = {};
    for (var i = 0; i < attributes.length; i++) {
        var attribute = attributes[i];
        var inputId = prefix + ucfirst(attribute);
        params[attribute] = $("#" + inputId).val()
    }
    return params
}


/**
 * 成功展示信息后跳转，失败展示信息
 *
 * @param data
 * @param modalId 成功隐藏模态框的ID
 * @param url 跳转的地址，如果没传，当前页面刷新
 */
function showMsgJump(data, url, modalId) {
    if (url == undefined) {
        url = window.location.href
    }
    if (isDataSuccess(data)) {
        showDataSuccess(data);
        if (modalId != undefined) {
            $('#' + modalId).modal("hide");
        }
        jumpUrl(url)
    } else {
        showDataError(data);
    }
}


/**
 * 首字母大小
 * @param str
 */
function ucfirst(str) {
    return str.substring(0, 1).toUpperCase() + str.substring(1)
}


layui.use('layer', function () {
    var layer = layui.layer;
});


function loading() {
    return index = layer.load(1, {
        shade: [0.1, '#fff'] //0.1透明度的白色背景
    });
}


function closeLodding() {
    layer.closeAll('loading');
}


$(function () {
    //Tips
    $('*[lay-tips]').on('mouseenter', function(){
        var content = $(this).attr('lay-tips');

        this.index = layer.tips('<div style="padding: 10px; font-size: 14px; color: #eee;">'+ content + '</div>', this, {
            time: -1
            ,maxWidth: 280
            ,tips: [3, '#3A3D49']
        });
    }).on('mouseleave', function(){
        layer.close(this.index);
    });
});