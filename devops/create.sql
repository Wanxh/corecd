#创建数据库
CREATE DATABASE IF NOT EXISTS `corecd` default character set utf8 collate utf8_general_ci;

USE corecd;

#配置表
CREATE TABLE IF NOT EXISTS `c_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT 'KEY',
  `value` varchar(500) DEFAULT '' COMMENT 'VALUE',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

#上线历史表
CREATE TABLE IF NOT EXISTS `c_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '项目id',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '上线状态，0：上线中，1：上线成功，2：上线失败',
  `md5` varchar(50) NOT NULL DEFAULT '' COMMENT '上线唯一id',
  `online` text COMMENT 'Api\\Online类序列化实例',
  `log_jenkins` mediumtext COMMENT '此次上线jenkins构建脚本',
  `log_rancher` text COMMENT '此次上线rancher错误日志',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_uid` (`uid`),
  KEY `index_pid` (`pid`),
  KEY `index_md5` (`md5`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8;

#项目表
CREATE TABLE IF NOT EXISTS `c_projects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `audit_uid` int(11) DEFAULT NULL COMMENT '审核的用户id',
  `project_name` varchar(50) NOT NULL DEFAULT '' COMMENT '项目名称（必须为英文）',
  `project_desc` varchar(50) NOT NULL DEFAULT '' COMMENT '项目描述',
  `project_use` varchar(50) NOT NULL DEFAULT '' COMMENT '项目用途',
  `project_main_address` varchar(1000) NOT NULL DEFAULT '' COMMENT '主项目地址',
  `project_main_branch` varchar(50) NOT NULL DEFAULT '' COMMENT '主项目分支',
  `project_sub_address` varchar(1000) DEFAULT '' COMMENT '子项目地址',
  `project_sub_branch` varchar(50) DEFAULT '' COMMENT '子项目分支',
  `project_sub_path` varchar(50) DEFAULT '' COMMENT '子项目集成后的目录名称',
  `batch_size` tinyint(255) unsigned NOT NULL DEFAULT '0' COMMENT '节点数量',
  `online_num` int(11) NOT NULL DEFAULT '0' COMMENT '上线成功次数',
  `ding` varchar(1000) NOT NULL COMMENT '钉钉机器人',
  `domain` varchar(200) DEFAULT '' COMMENT '域名',
  `domain_second_path` varchar(200) DEFAULT '' COMMENT '域名二级目录指向',
  `project_ci_address` varchar(1000) DEFAULT '' COMMENT '集成项目地址',
  `project_ci_branch` varchar(50) DEFAULT '' COMMENT '集成项目分支',
  `project_ci_path` varchar(50) DEFAULT '' COMMENT '集成项目集成后的目录名称',
  `ci_dockerfile` varchar(1000) DEFAULT '' COMMENT '集成时构建镜像的Dockerfile',
  `ci_run_file` varchar(50) DEFAULT '' COMMENT '集成启动的入口脚本',
  `ci_run` text COMMENT '集成shell代码，代替ci_run_file',
  `env_id` varchar(100) NOT NULL DEFAULT '' COMMENT '环境id',
  `env_name` varchar(100) DEFAULT '' COMMENT '环境名称',
  `env_desc` varchar(100) DEFAULT '' COMMENT '环境描述',
  `node_memory` int(11) DEFAULT '0' COMMENT '节点内存限制',
  `node_label` varchar(200) DEFAULT '' COMMENT '节点目标集群所具备的标签',
  `use_time` timestamp NULL DEFAULT NULL COMMENT '最近一次上线时间',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '最近一次修改时间',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '该项目状态，0：待审核，1：审核通过，2：审核拒绝，10：准备就绪，11：上线中，12：上线失败',
  `state` tinyint(2) NOT NULL DEFAULT '1' COMMENT '该项目状态，0：已删除 1:正常',
  PRIMARY KEY (`id`),
  KEY `index_uid` (`uid`),
  KEY `project_name` (`project_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

#用户表
CREATE TABLE IF NOT EXISTS `c_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `secret` varchar(200) NOT NULL DEFAULT '' COMMENT 'TOTP秘钥',
  `turl` varchar(500) DEFAULT '' COMMENT 'TOTP URL',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `role` tinyint(2) DEFAULT '0' COMMENT '用户类别，0：普通，1：管理员',
  PRIMARY KEY (`id`),
  KEY `index_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

#初始化配置项
INSERT INTO `c_config` (`name`,`value`) VALUES
('jenkins_address','https://jenkins.xx.com'),
('jenkins_username','xx'),
('jenkins_password','xx'),
('rancher_address','http://xx:8080'),
('rancher_key','xx'),
('rancher_secret','xx'),
('registry_address','registry.xx.com'),
('registry_username','xx'),
('registry_password','xx'),
('ding','xx'),
('listen_rate',3),
('listen_expire',120),
('node_cpu_shares',4000),
('node_memory',3670016000);

#日志表
CREATE TABLE IF NOT EXISTS `c_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(50) NOT NULL COMMENT '日志类型',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '项目id',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `params` json DEFAULT NULL COMMENT '参数',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8 COMMENT='通用日志表';

#用户项目关系表
CREATE TABLE IF NOT EXISTS `c_user_project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
  `pid` int(11) unsigned NOT NULL COMMENT '项目',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;