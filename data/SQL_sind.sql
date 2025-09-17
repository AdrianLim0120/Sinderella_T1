-- SINDERELLA TABLE
DROP TABLE IF EXISTS sinderellas;
CREATE TABLE IF NOT EXISTS sinderellas (
	sind_id INT(11) PRIMARY KEY AUTO_INCREMENT,
	sind_name VARCHAR(255) NOT NULL,
	sind_phno VARCHAR(11) NOT NULL,
	sind_pwd VARCHAR(255) NOT NULL,
	sind_address TEXT NOT NULL,
	sind_postcode VARCHAR(5) NOT NULL,
	sind_area VARCHAR(100) NOT NULL,
	sind_state VARCHAR(100) NOT NULL,
	sind_icno VARCHAR(20) NOT NULL,
    sind_dob DATE NOT NULL,
    sind_gender ENUM('male', 'female') NOT NULL,
    sind_emer_name VARCHAR(255) NOT NULL,
    sind_emer_phno VARCHAR(11) NOT NULL,
    sind_race VARCHAR(100) NOT NULL,
    sind_marital_status VARCHAR(100) NOT NULL,
    sind_no_kids INT(11), 
    sind_spouse_name VARCHAR(255),
    sind_spouse_phno VARCHAR(11),
    sind_spouse_ic_no VARCHAR(20),
    sind_spouse_occupation VARCHAR(100),
	sind_icphoto_path VARCHAR(255),
	sind_profile_path VARCHAR(255),
	sind_upline_id VARCHAR(11),
	sind_status VARCHAR(20),  -- Stores 'pending', 'active', 'inactive'
    acc_approved ENUM('pending', 'approve', 'reject') NOT NULL DEFAULT 'pending',
	sind_bank_name VARCHAR(100) NOT NULL,
    sind_bank_acc_no VARCHAR(20) NOT NULL,
    last_login_date DATETIME, 
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE sinderellas
ADD COLUMN sind_id_type VARCHAR(5) NOT NULL DEFAULT 'NI' AFTER sind_state;

DROP TABLE IF EXISTS sind_child;
CREATE TABLE IF NOT EXISTS sind_child (
    sind_child_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    child_name VARCHAR(255) NOT NULL,
    child_born_year INT(4) NOT NULL,
    child_occupation VARCHAR(100) NOT NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id)
);

-- ALTER TABLE sinderellas
-- ADD COLUMN acc_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER sind_status;

-- INSERT INTO sinderellas
-- (sind_name, sind_phno, sind_pwd, sind_address, sind_postcode, sind_area, sind_state, sind_icno, 
-- sind_icphoto_path, sind_profile_path, sind_upline_id, sind_status) VALUES
-- ('Sinderella One', '0123456789', 'pwd123', '12, Jalan ABC, Taman XYZ', '43000', 'Kajang', 'Selangor', '123456121234', 
-- '../img/ic_photo/0001.jpeg','../img/profile_photo/0001.jpg', '', 'pending');

-- SINDERELLA'S SERVICE AREA TABLE
DROP TABLE IF EXISTS sind_service_area;
CREATE TABLE IF NOT EXISTS sind_service_area (
    service_area_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    area VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id)
);

-- QUALIFIER TEST
DROP TABLE IF EXISTS qualifier_test;
CREATE TABLE IF NOT EXISTS qualifier_test (
    question_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    question_text TEXT NOT NULL,
    f_option0 TEXT NOT NULL, -- stores the correct option
    f_option1 TEXT NOT NULL, -- stores the false option
    f_option2 TEXT NOT NULL, -- stores the false option
    f_option3 TEXT NOT NULL  -- stores the false option
);

ALTER TABLE qualifier_test
    MODIFY question_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    MODIFY f_option0 TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    MODIFY f_option1 TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    MODIFY f_option2 TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    MODIFY f_option3 TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO `qualifier_test` (`question_id`, `question_text`, `f_option0`, `f_option1`, `f_option2`, `f_option3`) VALUES
(1, '根据培训内容，“家务”的定义是什么？', '可以在家中被清洗干净的物件或物品 ', '所有在家中进行的活动 ', '照顾老人和小孩 ', '花园维护和宠物照料'),
(2, '以下哪项不属于灰姑娘家政服务的范围？', '照顾婴儿 ', '吸尘抹地 ', '清洁厨房和厕所', '清洁风扇和窗口 '),
(3, '如果在顾客家遇到不明确的指示，应该怎么做？ ', '主动与顾客沟通确认 ', '按照自己的想法做', '停止工作，等待顾客回来 ', '询问其他灰姑娘的意见'),
(4, '服务后“不干净”和“不够时间”哪个情况更糟糕？ ', '服务后仍然不干净 ', '不够时间完成 ', '两者一样糟糕 ', '取决于顾客的看法'),
(5, '如果因为房子太大或杂物太多导致可能无法按时完成，应该怎么做？ ', '向顾客反映原因，并沟通后续处理（如下次补上或加时）', '故意拖延时间', '保持沉默，尽量做', '只做一部分就离开'),
(6, '在服务的最后阶段（例如最后1小时），建议做什么？ ', '与顾客做最后一次沟通确认 ', '加快速度完成剩余工作', '开始清洁自己的工具 ', '提前准备离开'),
(7, '离开顾客家前，必须检查什么？ ', '电源、垃圾桶、门窗等 ', '顾客的冰箱里有什么 ', '电视是否关闭', '是否收到小费'),
(8, '去顾客家之前的准备工作不包括哪项？', '提前与顾客确认时间地址', '准备好清洁工具和用品 ', '研究顾客的家庭背景 ', '佩戴必要的个人防护装备（可选）'),
(9, '第一次上门服务，关于到达时间，最重要的是什么？', '尽量避免迟到，最好准时或稍早到达 ', '可以稍微迟到一点', '比约定时间晚到半小时内都可以接受 ', '如果可能迟到，不需要通知顾客 '),
(10, '到达顾客家后，首先应该做什么？', '礼貌问候，并确认清洁区域和特殊要求 ', '立刻开始打扫卫生间', '找个地方休息一下 ', '要求顾客提供饮料'),
(11, '清洁流程建议的顺序是什么？', '按区域清洁，从上到下，从内到外 ', '从地面开始，再到高处 ', '从外部区域开始，再到内部', '先清洁厨房，再清洁卧室 '),
(12, '在客厅与卧室的清洁步骤通常包括什么？ ', '只吸尘或拖地 ', '擦拭家具、门窗、开关，吸尘/拖地，整理杂物', '重新布置家具', '清洗窗帘 '),
(13, '厨房清洁的重点区域有哪些？ ', '清洁台面、灶具、油烟机、水槽、橱柜表面、地面', '只清洁水槽 ', '清洗冰箱内部', '擦拭墙壁瓷砖'),
(14, '卫生间清洁应确保地面处于什么状态？', '干燥防滑', '潮湿以示清洁', '铺上地毯 ', '撒上香氛粉 '),
(15, '遇到特别肮脏的情况，建议采取什么措施？', '拍照记录（局部对比），与顾客沟通 ', '拒绝清洁该区域 ', '增加额外收费', '只做表面清洁'),
(16, '关于在顾客家拍照的规定是什么？ ', '只能拍局部对比照片（Before & After），不能拍全景', '可以随意拍摄整个家 ', '绝对禁止拍照 ', '需要先征得公司同意 '),
(17, '清洁结束后，应该做什么？ ', '检查工作、整理工具、请顾客检查确认', '立即离开 ', '等待顾客给小费 ', '和顾客聊家常 '),
(18, '在顾客家工作时，最重要的注意事项之一是什么？ ', '尊重顾客隐私，不乱动私人物品 ', '使用顾客的洗发水 ', '随意进入所有房间 ', '和顾客分享自己的私事 '),
(19, '如果顾客要求做一些服务范围之外的工作，应该如何应对？', '态度上不拒绝，评估自己是否能做、会做、时间是否允许，再决定', '立刻强硬拒绝', '不管三七二十一，直接帮忙做', '收取额外费用后再做'),
(20, '为什么建议在能力范围内帮助顾客处理额外要求？', '顾客满意度影响收入和客户保留 ', '公司规定必须做', '可以早点下班 ', '可以显得自己很能干'),
(21, '如果顾客对服务不满意，应避免哪种做法？ ', '询问顾客希望如何处理 ', '找借口或过多解释 ', '注意自己的语气和态度', '如果是自己的问题，表示歉意 '),
(22, '培训中提到的“服务意识”的核心是什么？', '以顾客为中心 ', '以公司利润为中心 ', '以快速完成任务为中心 ', '以个人方便为中心 '),
(23, '“主动服务”体现在哪里？ \r\n', '主动发现顾客可能需要的服务，而不是视而不见 ', '等待顾客明确指示', '只做分内的工作', '尽量减少与顾客的互动 '),
(24, '良好的服务态度包括哪些方面？', '积极、耐心、礼貌 ', '冷漠、快速、高效 ', '抱怨、拖延、不耐烦 ', '随意、健谈、自来熟 '),
(25, '具备服务意识对灰姑娘有什么好处？ ', '更容易保留顾客，获得稳定收入 ', '工作会更累 ', '可以更快地完成工作', '可以向顾客收取更高费用 '),
(26, '灰姑娘事业的第一个阶段（初期）主要依靠什么赚钱？', '亲自提供服务的劳力 ', '团队佣金 ', '投资回报 ', '公司分红'),
(27, '灰姑娘事业的第二个阶段（中期）的收入来源是什么？ ', '劳力收入和团队佣金 ', '只有劳力收入 ', '只有团队佣金 ', '公司补贴 '),
(28, '灰姑娘事业的第三个阶段（后期）的主要收入来源是什么？', '团队佣金', ' 劳力收入 ', '顾客小费 ', '销售清洁产品'),
(29, '培训中提到了哪两种主要的顾客群，其收入模式有何不同？ ', '固定顾客（收入可预测）和散客（收入不可预测）', '长期客户（收入稳定）和短期客户（收入波动）', '高端客户（收费高）和普通客户（收费标准）', '企业客户（按项目结算）和家庭客户（按小时结算）'),
(30, '根据培训内容，“散客”最显著的特点是什么？ ', '他们的服务日期和频率不固定', '他们总是需要深度清洁服务 ', '他们支付的费用比固定顾客高 ', '他们只在周末需要服务'),
(31, '为什么说拥有固定顾客对灰姑娘的收入至关重要？', '因为固定顾客能确保每周有稳定且可预测的收入', '因为固定顾客更容易沟通 ', ' 因为可以从固定顾客那里获得额外小费 ', '因为服务固定顾客可以减少交通时间 '),
(32, '培训中建议一天服务几位顾客比较合适，以避免过于劳累？ ', '1 位', '3 位 ', '4 位 ', '2位'),
(33, '当灰姑娘进入事业的第三阶段（后期），她的角色和服务的重心发生了什么变化？', '她服务的对象从顾客转变为她自己的下线灰姑娘团队 ', '她开始承担更多的清洁任务以增加收入', '她需要学习更复杂的清洁技术 ', '她转为只服务最高端的客户群体 '),
(34, '招募的下线灰姑娘可以来自哪里？ ', '可以来自全马其它地区 ', '只能来自自己居住的城市', '只能来自同一个州', '只能是自己的亲戚朋友 '),
(35, '进入事业的第三阶段，灰姑娘的工作重心会转变为？', '打造和带领自己的团队 ', '提供更多的清洁服务 ', '学习新的清洁技巧 ', '负责公司的行政工作 '),
(36, '根据过去的经验，哪种类型的顾客能带来稳定和轻松的工作？', '固定顾客', '散客', '只需一次服务的顾客 ', '要求最高的顾客'),
(37, '新人通常会忽略的事项之一，关于接单方式是什么？', '没习惯或很迟查看WhatsApp群，错过订单 ', '接太多单子', '拒绝所有不熟悉的单子', '只接高价单'),
(38, '接了单子后，服务前一天应该做什么，但新人有时会忘记？', '温馨提醒顾客服务时间和安排 ', '要求顾客预付费用 ', '向公司再次确认订单', '清洁自己的工具'),
(39, '新人在服务当天可能会犯什么严重错误？ ', '新人在服务当天可能会犯什么严重错误？ ', '忘记带手机 ', '穿错制服颜色', '提前到达'),
(40, '如果接了单却临时不能去服务，最错误的做法是什么？', '不通知任何人，直接不去 ', '提前一天通知顾客和公司', '只通知顾客，不通知公司 ', '自己找朋友代替去'),
(41, '与顾客沟通不足，容易导致什么问题？', '做顾客没要求的地方，遗漏顾客想做的地方', '工作时间延长', '顾客满意度提高', '清洁得特别干净'),
(42, '服务后忘记清理吸尘器或忘记关水电，属于哪一类问题？ ', '粗心大意，忘东忘西 ', '沟通问题 ', '效率问题 ', '工具问题 '),
(43, '灰姑娘平台的使命之一是什么？', '为打工人终止“手停口停”和“做最多赚最少”的局面', '让所有灰姑娘成为百万富翁', '提供全马来西亚最低价的家政服务', '只服务高端客户群体'),
(44, '灰姑娘平台的愿景是什么？', '让大马人民想到家务就想到灰姑娘', '成为国际化的家政公司', '开发自己的清洁产品品牌', '提供24小时随叫随到的服务 '),
(45, '以下哪项行为属于严重违规，可能导致灰姑娘身份被取消？', '未经公司私自与顾客建立服务关系（偷拿顾客）', '服务时偶尔接听私人电话', '清洁时打碎了一个杯子', '比预定时间晚了几分钟到达'),
(46, '偷窃、打劫、故意泄露顾客隐私等行为属于什么性质？', '刑事犯罪 ', '轻微违规 ', '服务态度问题', '沟通失误 '),
(47, '对于没有固定顾客的灰姑娘，离职规则是什么？ ', '可以随时离职，但需通知平台', '必须提前一个月通知 ', '必须找到接替者才能离职', '离职需要支付违约金'),
(48, '对于有固定顾客的灰姑娘，离职规则是什么？', '需提前1个月通知公司 ', '可以随时离职', '只需口头通知顾客即可 ', '工作满一年后才能离职'),
(49, '培训中提到的行政步骤包括哪些？', '灰姑娘Page Like、进 Group、发温馨提醒、接Order、注意衣着、打卡 ', '学习烹饪技巧', '参加公司聚餐', '学习高级管理课程'),
(50, '培训最后强调，在服务业中，让人满意服务的最高境界体现在哪个方面？', '顾客的“感觉”', '最低的价格', '最快的速度', '最干净的结果');

-- INSERT INTO qualifier_test (question_text, f_option0, f_option1, f_option2, f_option3) VALUES
-- ('question 1', 'true 1', 'false 1.1', 'false 1.2', 'false 1.3'),
-- ('question 2', 'true 2', 'false 2.1', 'false 2.2', 'false 2.3'),
-- ('question 3', 'true 3', 'false 3.1', 'false 3.2', 'false 3.3'),
-- ('question 4', 'true 4', 'false 4.1', 'false 4.2', 'false 4.3'),
-- ('question 5', 'true 5', 'false 5.1', 'false 5.2', 'false 5.3'),
-- ('question 6', 'true 6', 'false 6.1', 'false 6.2', 'false 6.3'),
-- ('question 7', 'true 7', 'false 7.1', 'false 7.2', 'false 7.3'),
-- ('question 8', 'true 8', 'false 8.1', 'false 8.2', 'false 8.3'),
-- ('question 9', 'true 9', 'false 9.1', 'false 9.2', 'false 9.3'),
-- ('question 10', 'true 10', 'false 10.1', 'false 10.2', 'false 10.3'),
-- ('question 11', 'true 11', 'false 11.1', 'false 11.2', 'false 11.3'),
-- ('question 12', 'true 12', 'false 12.1', 'false 12.2', 'false 12.3'),
-- ('question 13', 'true 13', 'false 13.1', 'false 13.2', 'false 13.3'),
-- ('question 14', 'true 14', 'false 14.1', 'false 14.2', 'false 14.3'),
-- ('question 15', 'true 15', 'false 15.1', 'false 15.2', 'false 15.3'),
-- ('question 16', 'true 16', 'false 16.1', 'false 16.2', 'false 16.3'),
-- ('question 17', 'true 17', 'false 17.1', 'false 17.2', 'false 17.3'),
-- ('question 18', 'true 18', 'false 18.1', 'false 18.2', 'false 18.3'),
-- ('question 19', 'true 19', 'false 19.1', 'false 19.2', 'false 19.3'),
-- ('question 20', 'true 20', 'false 20.1', 'false 20.2', 'false 20.3'),
-- ('question 21', 'true 21', 'false 21.1', 'false 21.2', 'false 21.3'),
-- ('question 22', 'true 22', 'false 22.1', 'false 22.2', 'false 22.3'),
-- ('question 23', 'true 23', 'false 23.1', 'false 23.2', 'false 23.3'),
-- ('question 24', 'true 24', 'false 24.1', 'false 24.2', 'false 24.3'),
-- ('question 25', 'true 25', 'false 25.1', 'false 25.2', 'false 25.3');

-- QUALIFIER TEST ATTEMPT HISTORY
DROP TABLE IF EXISTS qt_attempt_hist;
CREATE TABLE IF NOT EXISTS qt_attempt_hist (
    attempt_id INT(10) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    attempt_date DATETIME NOT NULL,
    attempt_score INT(10) NOT NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id)
);

-- SINDERELLA SERVICE AREA
DROP TABLE IF EXISTS sind_service_area;
CREATE TABLE IF NOT EXISTS sind_service_area (
    service_area_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    area VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id)
);

-- SINDERELLA LABEL TABLE
DROP TABLE IF EXISTS sind_label;
CREATE TABLE IF NOT EXISTS sind_label (
    slbl_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    slbl_name VARCHAR(255) NOT NULL,
    slbl_color_code VARCHAR(100) NOT NULL,
    slbl_status VARCHAR(100) NOT NULL DEFAULT 'active' -- Stores 'active', 'inactive'
);

-- SINDERELLA ID+LABEL TABLE
DROP TABLE IF EXISTS sind_id_label;
CREATE TABLE IF NOT EXISTS sind_id_label (
    sind_id_label_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    slbl_id INT(11) NOT NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id),
    FOREIGN KEY (slbl_id) REFERENCES sind_label(slbl_id)
);

-- SINDERELLA'S DOWNLINE TABLE [[[[NEW]]]]
DROP TABLE IF EXISTS sind_downline;
CREATE TABLE sind_downline (
    sind_id INT(11) NOT NULL, 
    dwln_phno VARCHAR(11) NOT NULL, 
    dwln_id INT(11) DEFAULT NULL, 
    created_at DATETIME NULL,
    PRIMARY KEY (sind_id, dwln_phno), 
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id), 
    FOREIGN KEY (dwln_id) REFERENCES sinderellas(sind_id) 
);

-- SINDERELLA AVAILABLE TIME - DATE [[[[ENHANCEMENT]]]]
DROP TABLE IF EXISTS sind_available_time;
CREATE TABLE IF NOT EXISTS sind_available_time (
    schedule_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    available_date DATE NOT NULL,
    available_from1 TIME NULL,
    available_from2 TIME NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id)
);

-- SINDERELLA AVAILABLE TIME - DAY [[[[ENHANCEMENT]]]]
DROP TABLE IF EXISTS sind_available_day;
CREATE TABLE IF NOT EXISTS sind_available_day (
    day_id INT(11) PRIMARY KEY AUTO_INCREMENT,
    sind_id INT(11) NOT NULL,
    day_of_week VARCHAR(10) NOT NULL,
    available_from1 TIME NULL,
    available_from2 TIME NULL,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id)
);

-- SINDERELLA REJECTED HISTORY TABLE
DROP TABLE IF EXISTS sind_rejected_hist;
CREATE TABLE sind_rejected_hist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sind_id INT(11) NOT NULL,
    booking_id INT(11) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sind_id) REFERENCES sinderellas(sind_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)
);