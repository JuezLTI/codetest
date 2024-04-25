<?php
require_once "../../initTsugi.php";
?>
<html>
	<body>
		<h3>lines preceded by -: expected output</h3>
		<h3>lines preceded by +: got output</h3>
		<pre>
<?php
$answer = new \CT\CT_Answer($_GET['answerId']);
foreach ($answer->getTestsOutput() as $key => $testOutput) {
	if(
		$USER->instructor
		|| $testOutput->visibleTest !== 0
	) {
		echo "Test: ".$key."\n";
		echo \CT\CT_Answer::getDiffWithSolution($testOutput->obtainedOutput, $testOutput->expectedOutput);
	}
}
?>
		</pre>