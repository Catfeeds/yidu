CREATE TABLE `hunuo_agent_apply` (
  `apply_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '申请人',
  `parent_agent_code` varchar(60) NOT NULL DEFAULT '' COMMENT '推荐代理商编号';
  `phone` varchar(20) NOT NULL COMMENT '联系电话',
  `province` varchar(20) NOT NULL COMMENT '省',
  `city` varchar(20) NOT NULL COMMENT '市',
  `district` varchar(20) NOT NULL COMMENT '区',
  `address` varchar(255) NOT NULL COMMENT '详细地址',
  `card` varchar(255) NOT NULL COMMENT '身份证号码',
  `face_card` varchar(255) NOT NULL COMMENT '身份证正面',
  `back_card` varchar(255) NOT NULL COMMENT '身份证反面',
  `is_pass` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否审核通过',
  `agent_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '代理商类型',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请时间',
  PRIMARY KEY (`apply_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `hunuo_agent_shop` (
  `shop_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `shop_code` varchar(60) NOT NULL DEFAULT '' COMMENT '店铺编码',
  `agent_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '店铺所属代理商ID',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '店铺所属用户ID',
  `chuzu_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0未出租 1已出租',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请时间',
  PRIMARY KEY (`shop_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE `hunuo_shop_goods_apply` (
  `apply_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '店铺ID',
  `goods_name` varchar(60) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_type` varchar(20) NOT NULL COMMENT '类别',
  `goods_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '商品介绍',
  `goods_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `goods_img` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
  `is_pass` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0待审核 1审核通过 2不通过',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '申请时间',
  PRIMARY KEY (`apply_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

ALTER TABLE hunuo_users ADD `is_agent` tinyint(3) NOT NULL DEFAULT '0' COMMENT '0非代理商 1代理商';
ALTER TABLE hunuo_users ADD `shop_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '所属店铺ID';
ALTER TABLE hunuo_users ADD `agent_code` varchar(60) NOT NULL DEFAULT '' COMMENT '代理商编号';
ALTER TABLE hunuo_users ADD `agent_type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '1小盘 2中盘 3大盘 4VIP';
ALTER TABLE hunuo_supplier ADD `agent_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '所属代理商ID';
ALTER TABLE hunuo_supplier ADD `shop_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '所属店铺ID';
ALTER TABLE hunuo_supplier ADD `shop_code` varchar(60) NOT NULL DEFAULT '' COMMENT '店铺编号';
ALTER TABLE hunuo_agent_shop ADD `chuzu_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '转出时间';
ALTER TABLE hunuo_agent_shop ADD `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '到期时间';
ALTER TABLE hunuo_agent_shop ADD `jh_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '激活时间';
ALTER TABLE hunuo_supplier_admin_user ADD `pwd` varchar(32) NOT NULL DEFAULT '' COMMENT '名文密码';
ALTER TABLE hunuo_goods ADD `factory_id` smallint(5) unsigned DEFAULT NULL COMMENT '厂家ID';

-- 厂家
CREATE TABLE `hunuo_factory` (
  `factory_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `factory_name` varchar(255) DEFAULT NULL COMMENT '厂家名称',
  `factory_type` varchar(255) DEFAULT NULL COMMENT '厂家类别',
  `contacts_name` varchar(100) NOT NULL DEFAULT '' COMMENT '联系人',
  `contacts_phone` varchar(50) NOT NULL DEFAULT '' COMMENT '联系电话',
  `goods_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '商品介绍',
  `business_card` varchar(255) NOT NULL COMMENT '营业执照',
  `tax_card` varchar(255) NOT NULL COMMENT '税务登记证',
  `code_card` varchar(255) NOT NULL COMMENT '机构代码证',
  `agent_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '所属代理商',
  `pass_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未审核 1审核通过 2审核不通过',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`factory_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 代理商租金返利记录
CREATE TABLE `hunuo_agent_rebate_log` (
  `rebate_log_id` mediumint(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `agent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代理商ID',
  `renew_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '续费金额',
  `rebate_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '返利金额',
  `pay_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式',
  `pay_name` varchar(120) NOT NULL DEFAULT '' COMMENT '支付名称',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '返利时间',
  PRIMARY KEY (`rebate_log_id`),
  KEY `shop_id` (`shop_id`),
  KEY `agent_id` (`agent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 会员消费返利记录
CREATE TABLE `hunuo_user_rebate_log` (
  `log_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `order_id` mediumint(8) NOT NULL COMMENT '订单id',
  `add_time` int(10) NOT NULL COMMENT '记录时间',
  `user_id` mediumint(8) NOT NULL COMMENT '用户id',
  `user_name` varchar(60) DEFAULT NULL COMMENT '用户名',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '返利金额',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 推荐区商品购买代理商奖励记录
CREATE TABLE `hunuo_agent_reward_log` (
  `log_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `order_id` mediumint(8) NOT NULL COMMENT '订单id',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `add_time` int(10) NOT NULL COMMENT '记录时间',
  `agent_id` mediumint(8) NOT NULL COMMENT '代理商id',
  `agent_name` varchar(60) DEFAULT NULL COMMENT '代理商名称',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '返利金额',
  `reward_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '奖励类型 1代理商下级店主商品奖励 2代理商引荐厂家商品奖励',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


ALTER TABLE hunuo_order_info ADD `is_rebate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户购买商品是否已返利';
ALTER TABLE hunuo_order_info ADD `is_reward` tinyint(1) NOT NULL DEFAULT '0' COMMENT '推荐区商品是否已奖励';
ALTER TABLE hunuo_order_goods ADD `is_recommend` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否推荐区商品';

-- 用户季度季度消费记录
CREATE TABLE `hunuo_user_quarter_consume` (
  `consume_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consume_paytime_start` int(10) unsigned NOT NULL COMMENT '季度开始时间',
  `consume_paytime_end` int(10) unsigned NOT NULL COMMENT '季度结束时间',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `is_pay_ok` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否满足分红条件',
  `pay_time` int(10) unsigned NOT NULL COMMENT '分红时间',
  `consume_all` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总消费金额',
  `consume_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '分红金额',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态(0:未结算或不满足分红条件,1:已分红)',
  PRIMARY KEY (`consume_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE hunuo_order_info ADD `consume_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '对应季度消费记录ID';
ALTER TABLE hunuo_users ADD `pid1` int(11) NOT NULL DEFAULT '0' COMMENT '上1级代理id';
ALTER TABLE hunuo_users ADD `pid2` int(11) NOT NULL DEFAULT '0' COMMENT '上2级i代理商d';
ALTER TABLE hunuo_users ADD `pid3` int(11) NOT NULL DEFAULT '0' COMMENT '上3级代理商id';
ALTER TABLE hunuo_agent_reward_log ADD `change_desc` varchar(255) NOT NULL COMMENT '操作描述';
ALTER TABLE hunuo_agent_rebate_log ADD `change_desc` varchar(255) NOT NULL COMMENT '操作描述';
ALTER TABLE hunuo_user_rebate_log ADD `change_desc` varchar(255) NOT NULL COMMENT '操作描述';
ALTER TABLE hunuo_user_quarter_consume ADD `change_desc` varchar(255) NOT NULL COMMENT '操作描述';
ALTER TABLE hunuo_agent_reward_log ADD `source_agent_id` int(11) NOT NULL DEFAULT '0' COMMENT '产品所属代理商';
ALTER TABLE hunuo_agent_reward_log ADD `goods_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '商品价格';

-- 银行卡列表
CREATE TABLE `hunuo_bank_list` (
  `bank_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `bank_name` varchar(255) NOT NULL COMMENT '银行名称',
  `icon` varchar(255) NOT NULL DEFAULT '' COMMENT '图标',
  `add_time` int(10) DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`bank_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `hunuo_user_bank` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `user_id` int(11) unsigned DEFAULT NULL COMMENT '用户id',
  `bank_id` int(11) unsigned DEFAULT NULL COMMENT '银行id',
  `bank_num` varchar(20) DEFAULT NULL COMMENT '银行卡号',
  `bank_branch` varchar(36) DEFAULT NULL COMMENT '银行支行',
  `bank_user` varchar(36) DEFAULT NULL COMMENT '银行开户名',
  `add_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认银行卡',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 店主优惠活动申请
CREATE TABLE `hunuo_activity_apply` (
  `apply_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `activity_name` varchar(255) DEFAULT NULL COMMENT '活动名称',
  `activity_desc` varchar(255) DEFAULT NULL COMMENT '优惠介绍',
  `goods_list` varchar(255) DEFAULT NULL COMMENT '优惠商品',
  `act_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0赠品 1现金减免 2：价格折扣',
  `start_time` int(10) unsigned NOT NULL COMMENT '优惠开始时间',
  `end_time` int(10) unsigned NOT NULL COMMENT '优惠结束时间',
  `min_amount` decimal(10,2) unsigned NOT NULL COMMENT '优惠下限',
  `max_amount` decimal(10,2) unsigned NOT NULL COMMENT '优惠上限',
  `act_type_ext` decimal(10,2) unsigned NOT NULL COMMENT '优惠数值',
  `shop_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '所属店铺',
  `pass_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未审核 1审核通过 2审核不通过',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`apply_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;