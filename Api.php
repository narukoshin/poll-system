<?php
    class Api{
        /**
         * @var string $action
         */
        private string $action;
        /**
         * @var object $db
         */
        private object $db;
        /**
         * Construction function
         * 
         * @return void
         */
        public function __construct(){
            // Connecting to the database
            $this->db = $this->DbConnection();
            // Getting action name
            $this->action = $_GET["action"] ?? false;
            // Calling action function
            return match ($this->action){
                'getpoll'       => $this->GetPoll(),
                'submitpoll'    => $this->SubmitPoll(),
                default         => false
            };
        }
        /**
         * @return object
         */
        private function DbConnection(): object{
            try {
                $db = new PDO("mysql:host=localhost;dbname=shit", "root", "");
            } catch (PDOException $err){
                // Printing json response
                echo json_encode([
                    "response"      => [
                        "message"   => "Failed to connect to database",
                        "error"     => $err->getMessage()
                    ],
                    "success"       => false
                ]);
                // Exiting
                exit;
            }
            return $db;
        }
        /**
         * Getting poll from database
         * 
         * @return void
         */
        private function GetPoll(){
            // Preparing query
            $query = $this->db->prepare("SELECT `poll_id`, `poll_name`, `poll_options` FROM `polls` ORDER BY `poll_id` DESC LIMIT 1");
            // Executing query
            if ($query->execute()){
                // Extracting data to variables
                 extract($query->fetch());
                // Printing json response
                 echo json_encode([
                     "question" => $poll_name,
                     "options"  => json_decode($poll_options),
                     "pollid"   => $poll_id,
                 ]);
                // echo json_encode([
                //     "response" => [
                //         "action" => __FUNCTION__,
                //         "poll"   => [
                //             "id"        => $poll_id,
                //             "name"      => $poll_name,
                //             "options"   => json_decode($poll_options)
                //         ]
                //     ]
                // ]);                
                // Exiting
                exit;
            } else {
                // Printing json response
                echo json_encode([
                    "response"      => [
                        "message"   => "Fetching poll data failed...",
                        "timestamp" => time()
                    ],
                    "success"       => false
                ]);
                // Exiting
                exit;
            }
        }
        /**
         * Submitting poll to the database
         * 
         * @return void
         */
        private function SubmitPoll(){
            // Decoding raw post data
            $data = json_decode(file_get_contents('php://input'), true);
            // Formatting data to variables
            extract($data);
            // Preparing query
            $query = $this->db->prepare("SELECT `poll_options` FROM `polls` WHERE `poll_id` = ?");
            // Executing query request
            if ($query->execute([$pollid])){
                // formatting data to variables
                extract($query->fetch());
                // Decoding json output of poll options
                $options = json_decode($poll_options);
                // Validating if submitted option contains in question options
                if (in_array($option, $options)){
                    // Preparing query
                    $query2 = $this->db->prepare("
                        INSERT INTO `submits` (
                            `poll_id`,
                            `submit_option`
                        ) VALUES (
                            :id,
                            :option
                        )
                    ");
                    // Executing query
                    if ($query2->execute(['id' => $pollid, 'option' => json_encode($option)])){
                        // Printing json response
                        echo json_encode([
                            "response" => [
                                "message" => "Submit was successful",
                                "info"    => [
                                    "poll_id"   => $pollid,
                                    "option"    => $option
                                ]
                            ],
                            "success"  => true
                        ]);
                        // Exiting
                        exit;
                    } else {
                        // Printing json response
                        echo json_encode([
                            "response"  => [
                                "message" => "Submit was unsuccessful"
                            ],
                            "success"     => false
                        ]);
                        // Exiting
                        exit;
                    }
                }
            } else {
                // Printing response
                echo json_encode([
                    "response" => [
                        "message" => "Error while fetching data from poll",
                        "info"    => [
                            "poll_id"   => $poll_id 
                        ],
                        "success" => false
                    ]
                ]);
                // Exiting
                exit;
            }
        }
    }(new Api);