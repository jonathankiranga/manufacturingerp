UPDATE
    stockmaster
SET
    stockmaster.averagestock = RAN.cost
FROM
    stockmaster SI
INNER JOIN
    StockRegister RAN
ON 
    SI.itemcode = RAN.itemcode;
