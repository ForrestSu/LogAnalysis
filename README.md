# LogAnalysis

LogAnalysis is a simple logcat tool, supported fix and common log, enjoy it.  
日志分析工具，适用于Fix日志和转换机日志。

## 1 Screenshots

![Effection](https://github.com/ForrestSu/LogAnalysis/raw/master/images/screenshot.png)

## 2 Performance Testing

performance  
![performance](https://github.com/ForrestSu/LogAnalysis/raw/master/images/performance.png)

## 3 How to deploy?

1 install php-fpm5 (or higher version); (will listen 127.0.0.1:9000 after started)  
2 install nginx;  
3 Execute the following bash command:

```bash
 cp -r LogAnalysis /data/
 cp LogAnalysis/nginx-conf/log-analysis-php.conf /etc/nginx/conf.d/
 sudo systemctl restart nginx
```

finally, visit it.

> http://127.0.0.1:6000/LogAnalysis/

## Author

Quan Sun

## Copyright

Copyright @2016-01-06
