<h2>公共函数</h2>
[TOC]

Author: sunquan
##1、QDII可用算法公共函数  
###1 QDII检查是否启用新可用算法
```java
头文件：newenablealgo.h
/***  2016-07-20 sunquan    ********************
函数功能：QDII判断该基金+资产单元+币种 是否启用新可用算法
输入参数：资金明细单元结构
输出参数：
返回值  ：1表示启用,0表示不启用
说明：
****************************************************/
int JudgeNewAlgorithmIsEnable(int iFundId,int iAssetId,char *sCurrencyNo)
```

###2 取日期iDate,iOffset天后的日期
```java
头文件：fundpub.h
/***************************************************************************
函数名称:GetTradeDate
函数功能:取日期iDate，iOffset天后的日期
输入参数:
 * l_iOffset  日期偏移天数
 * iMode:0:按自然日算;1:按自然日，如果目标日期是工作日则顺延; 2:按交易日算
 *                    3:T+N时,N为加的交易日个数,加了之后如果得到的不是交割日,则顺延一天
 *                    4:T+N时,N为加的自然日个数,加了之后如果得到的不是交割日,则顺延一天
 * sMarketNo 优先根据市场来取交易日类型
 * iFundID  如果传入市场为'0'根据基金投资的市场来获取交易日类型
输出参数:
返 回 值:0:正常；-1:出错
***************************************************************************/
int GetTradeDate(int iDate,int iOffset,char cMarketNo, int iMode,int iFundID,int *pReturnDate)
```

###3 阿斯达
####4 安达市