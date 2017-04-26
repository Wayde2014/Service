/*用户管理-用户信息表*/
CREATE TABLE `t_user_info` (
  `f_uid` int NOT NULL AUTO_INCREMENT COMMENT '用户uid(自增)',
  `f_nickname` varchar(50) DEFAULT NULL COMMENT '用户昵称',
  `f_mobile` varchar(50) NOT NULL COMMENT '手机号码',
  `f_realname` varchar(200) DEFAULT NULL COMMENT '真实姓名',
  `f_sex` tinyint DEFAULT 0 COMMENT '性别(0-未知,1-男,2-女)',
  `f_idcard` varchar(50) DEFAULT NULL COMMENT '身份证号码',
  `f_auth_status` smallint default 0 COMMENT '实名认证状态(0-未认证,100-已认证,-100-认证失败)',
  `f_usermoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '用户余额',
  `f_freezemoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `f_depositmoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '押金金额',
  `f_user_status` smallint default 0 COMMENT '用户状态(0-默认,100-已充值押金,200-已实名认证,-100-黑名单)',
  `f_lastdevice` varchar(200) DEFAULT NULL COMMENT '用户最近使用的设备',
  `f_regtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '注册时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_uid`),
  UNIQUE KEY `f_mobile` (`f_mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='用户管理-用户信息表';


/*用户管理-地址信息表*/
CREATE TABLE `t_user_address_info` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int NOT NULL COMMENT '用户uid',
  `f_province` varchar(100) NOT NULL COMMENT '省份名称',
  `f_city` varchar(100) NOT NULL COMMENT '城市名称',
  `f_address` varchar(1000) NOT NULL COMMENT '详细地址',
  `f_mobile` varchar(50) NOT NULL COMMENT '联系电话',
  `f_isactive` tinyint default 0 COMMENT '是否默认地址(0-否,1-是)',
  `f_status` tinyint default 0 COMMENT '地址状态(0-有效,-1-无效)',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户管理-地址信息表';


/*用户管理-账户流水表*/
CREATE TABLE `t_user_paylog` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int NOT NULL COMMENT '用户uid',
  `f_inout` tinyint not null comment '出入账类型(1-入账,2-出账)',
  `f_trademoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' comment '交易金额',
  `f_tradetype` varchar(200) NOT NULL COMMENT '交易类型(1001-余额充值,1002-押金充值,1004-订单解冻,1003-押金退款解冻,2001-押金退款,2002-押金退款冻结,2003-订单支付,2004-订单冻结)',
  `f_suborder` varchar(200) default null COMMENT '订单号', 
  `f_tradenote` varchar(1000) default null comment '交易备注',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户管理-账户流水表';


/*用户管理-充值订单表*/
CREATE TABLE `t_user_charge_order` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int NOT NULL COMMENT '用户uid',
  `f_paymoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '充值金额',
  `f_paytype` smallint NOT NULL COMMENT '充值类型(100-充值余额,200-充值押金)',
  `f_channel` varchar(50) not NULL COMMENT '充值渠道',
  `f_account` varchar(200) not NULL COMMENT '充值账号',
  `f_status` smallint default 0 COMMENT '订单状态(0-默认,100-充值成功,-100-充值失败)',
  `f_paynote` varchar(1000) default null comment '充值备注',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户管理-充值订单表';


/*用户管理-提款订单表*/
CREATE TABLE `t_user_draw_order` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int NOT NULL COMMENT '用户uid',
  `f_drawmoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '提款金额',
  `f_drawtype` smallint NOT NULL default 200 COMMENT '充值类型(100-余额提款,200-押金退款)',
  `f_channel` varchar(50) not NULL COMMENT '提款渠道',
  `f_account` varchar(200) not NULL COMMENT '提款账号',
  `f_status` smallint default 0 COMMENT '订单状态(0-默认,100-提款成功,-100-提款失败)',
  `f_drawnote` varchar(1000) default null comment '提款备注',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户管理-提款订单表';


/*用户管理-登录信息表*/
CREATE TABLE `t_user_login` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '自增长ID',
  `f_usercheck` varchar(200) NOT NULL COMMENT '登录ck',
  `f_uid` int NOT NULL COMMENT '登录用户ID',
  `f_deviceid` varchar(200) DEFAULT NULL COMMENT '登录设备号',
  `f_platform` tinyint DEFAULT 1 COMMENT '平台：1 web主站,2 android, 3 IOS, 4 H5',
  `f_ip` varchar(50) DEFAULT NULL COMMENT '登录ip',
  `f_remark` varchar(500) DEFAULT NULL COMMENT '附属信息',
  `f_expiretime` timestamp not null comment '登录过期时间(默认30天后过期)',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`),
  unique key (`f_usercheck`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 comment '用户管理-登录信息表';


/*门店管理-门店信息表*/
CREATE TABLE `t_store_info` (
  `f_sid` int NOT NULL AUTO_INCREMENT COMMENT '门店uid(自增)',
  `f_name` varchar(1000) not NULL COMMENT '门店名称',
  `f_icon` varchar(200) default null COMMENT '门店图标',
  `f_describle` TEXT COMMENT '门店描述',
  `f_address` varchar(2000) not null COMMENT '门店地址',
  `f_takeout` tinyint not null DEFAULT 1 COMMENT '是否支持外卖(0-不支持,1-支持)',
  `f_opentime` time default null comment '营业开始时间',
  `f_closetime` time default null comment '营业结束时间',
  `f_contact` varchar(200) default null comment '联系方式',
  `f_picture` varchar(500) comment '门店图片(多张以英文逗号分隔)',
  `f_maplat` varchar(50) DEFAULT NULL COMMENT '地图坐标-经度',
  `f_maplng` varchar(50) DEFAULT NULL COMMENT '地图坐标-纬度',  
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_sid`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='门店管理-门店信息表';


/*门店管理-门店桌型信息表*/
CREATE TABLE `t_store_tableinfo` (
  `f_id` smallint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_sid` int NOT NULL COMMENT '门店id',
  `f_name` varchar(1000) not NULL COMMENT '桌型名称',
  `f_picture` varchar(500) comment '桌型图片(多张以英文逗号分隔)',
  `f_seatnum` tinyint not null default 1 comment '可坐人数',
  `f_amount` tinyint not null default 1 comment '桌子数量',
  `f_tablenum` varchar(100) default null comment '桌号(以英文逗号分隔)',
  `f_status` tinyint not null default 1 comment '状态(1-有效,0-无效/已删除)',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店管理-门店信息表';


/*门店管理-放号信息表*/
CREATE TABLE `t_store_sellinfo` (
  `f_id` smallint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_sid` int NOT NULL COMMENT '门店id',
  `f_startdate` date default null comment '放号开始日期',
  `f_enddate` date default null comment '放号结束日期',
  `f_starttime` time default null comment '放号开始时间',
  `f_endtime` time default null comment '放号结束时间',
  `f_tabletype` varchar(100) default null comment '放号桌型ID(以英文逗号分隔)',
  `f_sellnum` tinyint default 1 comment '放号数量',
  `f_tablenum` varchar(100) default null comment '放号桌号(以英文逗号分隔)',
  `f_status` tinyint not null default 1 comment '状态(1-有效,0-无效/已删除)',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店管理-放号信息表';


/*门店管理-折扣信息表*/
CREATE TABLE `t_store_discount` (
  `f_id` smallint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_sid` int NOT NULL COMMENT '门店id',
  `f_did` int NOT NULL COMMENT '菜肴id',
  `f_type` tinyint not null default 1 comment '折扣类型(1-直减,2-打折)', 
  `f_disnum` smallint not null comment '折扣数量',
  `f_startdate` date default null comment '折扣开始日期',
  `f_enddate` date default null comment '折扣结束日期',
  `f_starttime` time default null comment '折扣开始时间',
  `f_endtime` time default null comment '折扣结束时间',
  `f_status` tinyint not null default 1 comment '状态(1-有效,0-无效/已删除)',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店管理-折扣信息表';


/*门店管理-资金信息表*/
CREATE TABLE `t_store_account` (
  `f_id` smallint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_sid` int NOT NULL COMMENT '门店id',
  `f_depositmoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '押金金额',
  `f_storemoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '账户余额',
  `f_proceeds` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '收益金余额',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店管理-资金信息表';


/*门店管理-配送员信息表*/
CREATE TABLE `t_store_distripersion` (
  `f_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '配送人员ID',
  `f_username` varchar(200) DEFAULT NULL COMMENT '配送人员名字',
  `f_mobile` varchar(200) DEFAULT NULL COMMENT '配送员联系方式',
  `f_state` int(10) NOT NULL COMMENT '配送人员状态（0初始，-1已禁止）',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='门店管理-配送员信息表';


/*后台管理-用户信息表*/
CREATE TABLE `t_admin_userinfo` (
  `f_uid` int NOT NULL AUTO_INCREMENT COMMENT '用户uid(自增)',
  `f_username` varchar(50) NOT NULL COMMENT '用户名',
  `f_realname` varchar(200) DEFAULT NULL COMMENT '真实姓名',
  `f_password` varchar(32) NOT NULL COMMENT '用户密码',
  `f_status` smallint default 100 COMMENT '用户状态(默认100-正常用户,-100-禁用用户)',
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_uid`),
  UNIQUE KEY `f_username` (`f_username`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='后台管理-用户信息表';


/*后台管理-角色信息表*/
CREATE TABLE `t_admin_role` (
  `f_rid` int NOT NULL AUTO_INCREMENT COMMENT '角色rid(自增)',
  `f_name` varchar(100) NOT NULL COMMENT '角色名称',
  `f_describle` varchar(1000) default NULL COMMENT '角色描述',
  `f_status` tinyint not null default 1 comment '状态(1-有效,0-无效/已删除)',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='后台管理-角色信息表';


/*后台管理-模块信息表*/
CREATE TABLE `t_admin_module` (
  `f_mid` int NOT NULL AUTO_INCREMENT COMMENT '模块mid(自增)',
  `f_name` varchar(100) NOT NULL COMMENT '模块名称',
  `f_describle` varchar(1000) default NULL COMMENT '模块描述',
  `f_url` varchar(1000) default NULL COMMENT '链接地址',
  `f_parentid` smallint default 0 comment '父模块ID',
  `f_order` smallint default 1 comment '显示顺序',  
  `f_status` tinyint not null default 1 comment '状态(1-有效,0-无效/已删除)',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='后台管理-模块信息表';


/*后台管理-用户角色关联信息表*/
CREATE TABLE `t_admin_user_role` (
  `f_id` int not null AUTO_INCREMENT comment '自增ID',
  `f_uid` int NOT NULL COMMENT '用户ID',
  `f_rid` int NOT NULL COMMENT '角色ID',
  `f_describle` varchar(1000) default NULL COMMENT '说明',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='后台管理-用户角色关联信息表';


/*后台管理-角色模块关联信息表*/
CREATE TABLE `t_admin_role_module` (
  `f_id` int not null AUTO_INCREMENT comment '自增ID',
  `f_rid` int NOT NULL COMMENT '角色ID',
  `f_mid` int NOT NULL COMMENT '模块ID',
  `f_describle` varchar(1000) default NULL COMMENT '说明',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='后台管理-角色模块关联信息表';

/*菜肴管理-口味信息表*/
CREATE TABLE `t_food_tastes` (
  `f_tid` int NOT NULL AUTO_INCREMENT COMMENT '口味ID(自增)',
  `f_tname` varchar(200) NOT NULL COMMENT '口味名称',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='菜肴管理-口味信息表';


/*菜肴管理-菜系信息表*/
CREATE TABLE `t_food_cuisine` (
  `f_cid` int NOT NULL AUTO_INCREMENT COMMENT '菜系ID(自增)',
  `f_cname` varchar(200) NOT NULL COMMENT '菜系名称',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='菜肴管理-菜系信息表';


/*菜肴管理-菜品信息表*/
CREATE TABLE `t_food_dishes` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '菜肴ID(自增)',
  `f_name` varchar(200) NOT NULL COMMENT '菜品名称',
  `f_price` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '菜品价格',
  `f_state` smallint NOT NULL COMMENT '菜品状态（-1已停售， 0初始， 1预售，100已售完）',
  `f_tastesid` int NOT NULL COMMENT '口味ID',
  `f_cuisineid` int NOT NULL COMMENT '菜系ID',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='菜肴管理-菜品信息表';


/*菜肴管理-菜单信息表*/
CREATE TABLE `t_food_menu` (
  `f_id` int NOT NULL AUTO_INCREMENT comment '菜单ID(自增)',
  `f_oid` int NOT NULL comment 'unknow',
  `f_foodid` int NOT NULL COMMENT '菜单ID',
  `f_foodname` varchar(200) DEFAULT NULL COMMENT '菜名',
  `f_foodicon` varchar(200) DEFAULT NULL COMMENT '菜品图片',
  `f_foodprice` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '菜品价格',
  `f_foodnum` varchar(200) DEFAULT NULL COMMENT '菜品数量',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='菜肴管理-菜单信息表';


/*订单管理-订单信息表*/
CREATE TABLE `t_order_info` (
  `f_oid` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '订单ID,唯一标识',
  `f_shopid` smallint NOT NULL COMMENT '店铺ID',
  `f_type` tinyint NOT NULL DEFAULT 1 COMMENT '订单类型（1,外卖订单  2,食堂订单）',
  `f_status` smallint NOT NULL DEFAULT 0 COMMENT '订单状态（0,初始 1,未付款 2,已付款 3,配送中 4,配送完成 5,用餐中 100,已完成 -100逾期）',
  `f_allmoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '订单总金额',
  `f_paymoney` decimal(19,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '已支付金额',
  `f_paytype` varchar(200) DEFAULT NULL COMMENT '支付方式',
  `f_mealsnum` tinyint DEFAULT 0 COMMENT '就餐人数',
  `f_addressid` int DEFAULT NULL COMMENT '配送地址ID',
  `f_deliveryid` int DEFAULT NULL COMMENT '配送员ID',
  `f_startime` timestamp DEFAULT NULL COMMENT '用餐开始时间',
  `f_endtime` timestamp DEFAULT NULL COMMENT '用餐结束时间',  
  `f_addtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '添加时间',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_oid`)
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='订单管理-订单信息表';

/*用户管理-短信发送记录表*/
CREATE TABLE `t_user_smslog` (
  `f_id` int NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `f_uid` int NOT NULL COMMENT '用户uid',
  `f_mobile` varchar(50) NOT NULL COMMENT '手机号码',
  `f_count` int default 0 COMMENT '发送成功次数',
  `f_lasttime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`f_id`),
  UNIQUE KEY (`f_uid`,`f_mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户管理-短信发送记录表';