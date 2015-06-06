<?PHP
include "config.php";

header('Content-Type: application/json');

$json = file_get_contents("php://input");
if (!$json) {
	$jsonData = Array(
		"error" => "Method Not Allowed",
		"errorMessage" => "The method specified in the request is not allowed for the resource identified by the request URI"
	);
	echo json_encode($jsonData);
	die();
}

$json = json_decode($json, true);

$username = trim(mysqli_real_escape_string($MySQL_CONNECT, $json["username"]));
$password = HashPassword(trim(mysqli_real_escape_string($MySQL_CONNECT, $json["password"])));
if (isset($json["clientToken"]) && !empty($json["clientToken"])) {
	$clientToken = trim(mysqli_real_espace_string($MySQL_CONNECT, $json["clientToken"]));
}

$exec = mysqli_query($MySQL_CONNECT, "SELECT *  FROM `users` WHERE `username` = '".$username."'");
$data = @mysqli_fetch_array($exec);

if (!isset($json["username"])) {
	$jsonData = Array(
		"error" => "IllegalArgumentException",
		"errorMessage" => "credentials can not be null."
	);
} elseif (empty($json["username"])) {
	$jsonData = Array(
		"error" => "IllegalArgumentException",
		"errorMessage" => "credentials can not be null."
	);
} elseif (!isset($json["password"])) {
	$jsonData = Array(
		"error" => "IllegalArgumentException",
		"errorMessage" => "credentials can not be null."
	);
} elseif (empty($json["password"])) {
	$jsonData = Array(
		"error" => "IllegalArgumentException",
		"errorMessage" => "credentials can not be null."
	);
} elseif ($exec == false) {
	$jsonData = Array(
		"error" => "ForbiddenOperationException",
		"errorMessage" => "Invalid credentials. Invalid username or password."
	);
} elseif (count($data) == 0) {
	$jsonData = Array(
		"error" => "ForbiddenOperationException",
		"errorMessage" => "Invalid credentials. Invalid username or password."
	);
} else {
	if ($data["password"] == $password) {
		if (!isset($clientToken)) {
			$clientToken = GenClientToken();
		}
		$accessToken = GenAccessToken();
		mysqli_query($MySQL_CONNECT, "INSERT INTO `tokens` (`accessToken`, `clientToken`, `uuid`) VALUES ('".$accessToken."', '".$clientToken."', '".$data["uuid"]."');");
		$jsonData = Array(
			"accessToken" => $accessToken,
			"clientToken" => $clientToken,
			"selectedProfile" => Array(
				"id" => $data["uuid"],
				"name" => $data["username"]
			),
			"availableProfiles" => Array(
				Array(
					"id" => $data["uuid"],
					"name" => $data["username"]
				)
			)
		);
	} else {
		$jsonData = Array(
			"error" => "ForbiddenOperationException",
			"errorMessage" => "Invalid credentials. Invalid username or password."
		);
	}
}

echo json_encode($jsonData);
?>