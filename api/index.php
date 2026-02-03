<?php

$action = $_GET['action'] ?? null;
switch ($action) {
    case 'userLogin':
        require_once './controllers/UserLoginController.php';
        $controller = new UserLoginController();
        $controller->login();
        break;

    case 'getStationName':  
        require_once './controllers/StationController.php';
        $controller = new StationController();
        $controller->getStationName();
        break;
        
    case 'question':  
        require_once './controllers/QuestionsController.php';
        $controller = new QuestionsController();
        $controller->handleRequest(); 
        break;
    
    case 'Subquestion':  
        require_once './controllers/SubQuestionsController.php';
        $controller = new SubQuestionsController();
        $controller->handleRequest(); 
        break;
        
    case 'get_user_name_report':  
        require_once './controllers/GetUserNameReportController.php';
        $controller = new GetUserNameReportController();
        $controller->handleRequest(); 
        break;
        
    case 'parameters':
        require_once './controllers/ParametersController.php';
        $controller = new ParametersController();
        $controller->handleRequest();
        break;
        
     case 'page':
        require_once './controllers/PageController.php';
        $controller = new PageController();
        $controller->handleRequest(); 
         break;

    default:
        echo json_encode([
            "status" => false,
            "message" => "Invalid API action"
        ]);
}