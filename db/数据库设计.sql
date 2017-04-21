/*用户信息表*/
CREATE TABLE `t_user_info` (
  `f_uid` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户uid(自增)',
  `f_nickname` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `f_mobile` varchar(50) NOT NULL COMMENT '手机号码',
  `f_realname` varchar(200) DEFAULT NULL COMMENT '真实姓名',
  `f_sex` int(11) DEFAULT 0 COMMENT '性别(0-未知,1-男,2-女)',
  `f_idcard` varchar(50) DEFAULT NULL COMMENT '身份证号码',
  `f_auth_status` int(11) default 0 COMMENT '实名认证状态(0-未认证,100-已认证,-100-认证失败)',
  `f_usermoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '用户余额',
  `f_freezemoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `f_depositmoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '押金金额',
  `f_user_status` int(11) default 0 COMMENT '用户状态(0-默认,100-已充值押金,200-已实名认证,-100-黑名单)',
  `f_lastdevice` varchar(200) DEFAULT NULL COMMENT '用户最近使用的设备',
  `f_regtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '注册时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_uid`),
  UNIQUE KEY `f_mobile` (`f_mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='用户信息表';


/*用户地址信息表*/
CREATE TABLE `t_user_address_info` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int(11) NOT NULL COMMENT '用户uid',
  `f_province` varchar(100) NOT NULL COMMENT '省份名称',
  `f_city` varchar(100) NOT NULL COMMENT '城市名称',
  `f_address` varchar(1000) NOT NULL COMMENT '详细地址',
  `f_mobile` varchar(50) NOT NULL COMMENT '联系电话',
  `f_isactive` int(11) default 0 COMMENT '是否默认地址(0-否,1-是)',
  `f_status` int(11) default 0 COMMENT '地址状态(0-有效,-1-无效)',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户地址信息表';


/*用户账户流水表*/
CREATE TABLE `t_user_paylog` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int(11) NOT NULL COMMENT '用户uid',
  `f_inout` int(11) not null comment '出入账类型(1-入账,2-出账)',
  `f_trademoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' comment '交易金额',
  `f_tradetype` varchar(200) NOT NULL COMMENT '交易类型(1001-余额充值,1002-押金充值,1004-订单解冻,1003-押金退款解冻,2001-押金退款,2002-押金退款冻结,2003-订单支付,2004-订单冻结)',
  `f_suborder` varchar(200) default null COMMENT '订单号', 
  `f_tradenote` varchar(1000) default null comment '交易备注',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户账户流水表';


/*用户充值订单表*/
CREATE TABLE `t_user_charge_order` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int(11) NOT NULL COMMENT '用户uid',
  `f_paymoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '充值金额',
  `f_paytype` int(11) NOT NULL COMMENT '充值类型(100-充值余额,200-充值押金)',
  `f_channel` varchar(50) not NULL COMMENT '充值渠道',
  `f_account` varchar(200) not NULL COMMENT '充值账号',
  `f_status` int(11) default 0 COMMENT '订单状态(0-默认,100-充值成功,-100-充值失败)',
  `f_paynote` varchar(1000) default null comment '充值备注',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户充值订单表';


/*用户提款订单表*/
CREATE TABLE `t_user_draw_order` (
  `f_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int(11) NOT NULL COMMENT '用户uid',
  `f_drawmoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '提款金额',
  `f_drawtype` int(11) NOT NULL default 200 COMMENT '充值类型(100-余额提款,200-押金退款)',
  `f_channel` varchar(50) not NULL COMMENT '提款渠道',
  `f_account` varchar(200) not NULL COMMENT '提款账号',
  `f_status` int(11) default 0 COMMENT '订单状态(0-默认,100-提款成功,-100-提款失败)',
  `f_drawnote` varchar(1000) default null comment '提款备注',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户提款订单表';