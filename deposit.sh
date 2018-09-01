
read exchange
while [ 1 ]
do
  php index.php coind TextscanWalletBTC/$exchange
  
  sleep 60s
done