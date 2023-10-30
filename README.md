# 访问追踪和统计器 - Visitation Tracker & Statistics

## 概述

访问追踪和统计器是一个轻量级工具，用于记录和统计用户访问信息。该项目由三个PHP文件组成，分别用于记录访问信息、查询访问总数以及计算IP统计信息。它注重简单性和效率，提供基本的追踪功能，适用于那些更喜欢直接的访问追踪解决方案的项目。


## 文件说明

### 1. get_visitors.php

`get_visitors.php`用于记录用户访问信息，并将其保存到数据库中。它包括了连接到数据库、创建结果表、记录访问信息的功能。 通过访问该文件，用户的访问信息将被记录并存储到数据库中。

### 2. get_visit_count.php

`get_visit_count.php`用于查询数据库中访问记录的总数，并返回该总数。它包括了连接到数据库、查询访问记录行数、输出访问总数的功能。通过访问该文件，将获取并返回访问总数。

### 3. calculate_ip_statistics.php

`calculate_ip_statistics.php`是轻量追踪器的一部分，用于计算每个相同IP地址的总次数、最后一次访问时间、首次访问时间和地址。这一功能在轻量追踪器中起到关键作用。通过访问该文件，以获取完整的访问统计信息。

## 使用说明

1. 将整个文件部署到服务器上。
2. 配置数据库连接信息（主机名、用户名、密码、数据库名）。
3. 将以下JS代码添加网站首页的文件里即可，注意url路径
```js
//获取访问总数和时间IP
$(document).ready(function() {
        //获取数据表IP列表转换访问量
        $.ajax({
            url: 'get_visit_count.php',
            method: 'GET',
            success: function(response) {
                $('#visit-count').text(response);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
        //获取当前访问的用户IP，写入到数据库
        $.ajax({
            url: 'get_visitors.php?random=' + Math.random(),
            method: 'GET',
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    });
```
4.最后，在底部居中展示网站的访问量

```html
<!-- 访问次数 -->
<a href="calculate_ip_statistics.php"><div id="visit-count"></div></a>
```

5.首次运行会抛出错误，这是因为索引表不存在，刷新一次页面即可，它将自动创建

6.通过点击a标签访问calculate_ip_statistics文件，以更新完整的访问统计信息。

## 值得注意的事项

- 请确保服务器环境支持PHP和MySQL，且数据库链接信息无误。
- calculate_ip_statistics 避免频繁访问，以减轻数据库负担。建议是主动点击或定时更新
- **定期维护：** 定期评估并维护相关数据库表，以保持持续的最佳性能。
- **优化设计：** 该追踪器经过精心设计，注重简单性而不损害效率。
- **缓存机制：** 在高流量场景下，考虑实施缓存机制，以提升整体性能。
- **访问统计：** 提供详尽的洞察，包括总访问次数、最后访问时间、首次访问时间和访客地址。
- **核心效率：** 以简单性和效率为设计理念，确保在各类项目中的无缝集成。


## 贡献者

- [Keeleycenc](https://keeleycenc.com)

---

如果您在使用过程中遇到问题或有改进建议，请随时提出。感谢您的使用！
