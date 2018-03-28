
rmdir2($trPath);
$transaction->delete();
$transaction->writeFile();
unset($transaction);
$error = writeJSONfile($libraryPath . "transactions_open.json", $transactions);