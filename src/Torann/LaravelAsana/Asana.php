<?php namespace Torann\LaravelAsana;

class Asana {

    private $config;

    private $timeout = 10;

    public $error;

    public $deafultWorkspaceId;
    public $deafultProjectId;

    private $endPointUrl = "https://app.asana.com/api/1.0/";
    private $apiKey;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->apiKey = $config['key'];

        $this->deafultWorkspaceId = $config['workspaceId'];
        $this->deafultProjectId = $config['projectId'];
    }

    /**
     * Returns the full user record for a single user.
     * Call it without parameters to get the users info of the owner of the API key.
     *
     * @param  string $userId
     * @return string JSON or null
     */
    public function getUserInfo($userId = null)
    {
        if(is_null($userId)) $userId = "me";
        return $this->askAsana('GET', $this->endPointUrl."users/{$userId}");
    }

    /**
     * Returns the user records for all users in all workspaces you have access.
     *
     * @return string JSON or null
     */
    public function getUsers()
    {
        return $this->askAsana('GET', $this->endPointUrl.'users');
    }

    /**
     * Function to create a task.
     * For assign or remove the task to a project, use the addProjectToTask and removeProjectToTask.
     *
     *
     * @param array $data Array of data for the task following the Asana API documentation.
     * Example:
     *
     * array(
     *     "workspace" => "1768",
     *     "name" => "Hello World!",
     *     "notes" => "This is a task for testing the Asana API :)",
     *     "assignee" => "176822166183",
     *     "followers" => array(
     *         "37136",
     *         "59083"
     *     )
     * )
     *
     * @return string JSON or null
     */
    public function createTask( $data )
    {
        $data = array_merge(array(
            'workspace' => $this->deafultWorkspaceId
        ), $data);

        return $this->askAsana('POST', $this->endPointUrl.'tasks', json_encode( array('data' => $data) ));
    }

    /**
     * Returns task information
     *
     * @param  string $taskId
     * @return string JSON or null
     */
    public function getTask($taskId)
    {
        return $this->askAsana('GET', $this->endPointUrl."tasks/{$taskId}");
    }

    /**
     * Returns sub-task information
     *
     * @param  string $taskId
     * @return string JSON or null
     */
    public function getSubTasks($taskId)
    {
    	return $this->askAsana('GET', $this->endPointUrl."tasks/{$taskId}/subtasks");
    }

    /**
     * Updates a task
     *
     * @param  string $taskId
     * @param  array $data See, createTask function comments for proper parameter info.
     * @return string JSON or null
     */
    public function updateTask($taskId, $data)
    {
        return $this->askAsana('PUT', $this->endPointUrl."tasks/{$taskId}", json_encode( array('data' => $data) ));
    }

    /**
     * Add Attachment to a task
     *
     * @param  string $taskId
     * @param  array $file
     * @return string JSON or null
     */
    public function addTaskAttachment($taskId, $file)
    {
        $data = array(
            'file' => $this->addPostFile($file)
        );

        return $this->askAsana('POST', $this->endPointUrl."tasks/{$taskId}/attachments", $data);
    }

    /**
     * Returns the projects associated to the task.
     *
     * @param  string $taskId
     * @return string JSON or null
     */
    public function getProjectsForTask($taskId)
    {
        return $this->askAsana('GET', $this->endPointUrl."tasks/{$taskId}/projects");
    }

    /**
     * Adds a project to task. If successful, will return success and an empty data block.
     *
     * @param  string $taskId
     * @param  string $projectId
     * @return string JSON or null
     */
    public function addProjectToTask($taskId, $projectId = null)
    {
        $data = array(
            'project' => $projectId ?: $this->deafultProjectId
        );

        return $this->askAsana('POST', $this->endPointUrl."tasks/{$taskId}/addProject", json_encode( array('data' => $data) ));
    }

    /**
     * Removes project from task. If successful, will return success and an empty data block.
     *
     * @param  string $taskId
     * @param  string $projectId
     * @return string JSON or null
     */
    public function removeProjectToTask($taskId, $projectId = null)
    {
        $data = array(
            'project' => $projectId ?: $this->deafultProjectId
        );

        return $this->askAsana('POST', $this->endPointUrl."tasks/{$taskId}/removeProject", json_encode( array('data' => $data) ));
    }

    /**
     * Returns task by a given filter.
     * For now (limited by Asana API), you may limit your query either to a specific project or to an assignee and workspace
     *
     * NOTE: As Asana API says, if you filter by assignee, you MUST specify a workspaceId and viceversa.
     *
     * @param  array $filter The filter with optional values.
     *
     * array(
     *     "assignee" => "",
     *     "project" => 0,
     *     "workspace" => 0
     * )
     *
     * @return string JSON or null
     */
    public function getTasksByFilter($filter = array("assignee" => "", "project" => "", "workspace" => ""))
    {
        $url = "";
        $filter = array_merge(array("assignee" => "", "project" => "", "workspace" => ""), $filter);
        $url .= $filter["assignee"] != ""?"&assignee={$filter["assignee"]}":"";
        $url .= $filter["project"] != ""?"&project={$filter["project"]}":"";
        $url .= $filter["workspace"] != ""?"&workspace={$filter["workspace"]}":"";
        if(strlen($url) > 0) $url = "?".substr($url, 1);

        return $this->askAsana('GET', $this->endPointUrl."tasks{$url}");
    }

    /**
     * Returns the list of stories associated with the object.
     * As usual with queries, stories are returned in compact form.
     * However, the compact form for stories contains more information by default than just the ID.
     * There is presently no way to get a filtered set of stories.
     *
     * @param  string $taskId
     * @return string JSON or null
     */
    public function getTaskStories($taskId)
    {
        return $this->askAsana('GET', $this->endPointUrl."tasks/{$taskId}/stories");
    }

    /**
     * Adds a comment to a task.
     * The comment will be authored by the authorized user, and timestamped when the server receives the request.
     *
     * @param  string $taskId
     * @param  string $text
     * @return string JSON or null
     */
    public function commentOnTask($taskId, $text = "")
    {
        $data = array(
            'text' => $text
        );

        return $this->askAsana('POST', $this->endPointUrl."tasks/{$taskId}/stories", json_encode( array('data' => $data) ));
    }

    /**
     * Adds a tag to a task. If successful, will return success and an empty data block.
     *
     * @param  string $taskId
     * @param  string $tagId
     * @return string JSON or null
     */
    public function addTagToTask($taskId, $tagId)
    {
        $data = array(
            "tag" => $tagId
        );

        return $this->askAsana('POST', $this->endPointUrl."tasks/{$taskId}/addTag", json_encode( array('data' => $data) ));
    }

    /**
     * Removes a tag from a task. If successful, will return success and an empty data block.
     *
     * @param  string $taskId
     * @param  string $tagId
     * @return string JSON or null
     */
    public function removeTagFromTask($taskId, $tagId)
    {
        $data = array(
            "tag" => $tagId
        );

        return $this->askAsana('POST', $this->endPointUrl."tasks/{$taskId}/removeTag", json_encode( array('data' => $data) ));
    }

    /**
     * Function to create a project.
     *
     * @param array $data Array of data for the project following the Asana API documentation.
     * Example:
     *
     * array(
     *     "workspace" => "1768",
     *     "name" => "Foo Project!",
     *     "notes" => "This is a test project"
     * )
     *
     * @return string JSON or null
     */
    public function createProject($data)
    {
        return $this->askAsana('POST', $this->endPointUrl.'projects', json_encode( array('data' => $data) ));
    }

    /**
     * Returns the full record for a single project.
     *
     * @param  string $projectId
     * @return string JSON or null
     */
    public function getProject($projectId = null)
    {
        $projectId = $projectId ?: $this->deafultProjectId;

        return $this->askAsana('GET', $this->endPointUrl."projects/{$projectId}");
    }

    /**
     * Returns the projects in all workspaces containing archived ones or not.
     *
     * @param  boolean $archived Return archived projects or not
     * @param  string  $opt_fields Return results with optional parameters
     * @return string  JSON or null
     */
    public function getProjects($archived = false, $opt_fields = "")
    {
        $archived = $archived?"true":"false";
        $opt_fields = ($opt_fields != "")?"&opt_fields={$opt_fields}":"";

        return $this->askAsana('GET', $this->endPointUrl."projects?archived={$archived}{$opt_fields}");
    }

    /**
     * Returns the projects in provided workspace containing archived ones or not.
     *
     * @param  string  $workspaceId
     * @param  boolean $archived Return archived projects or not
     * @return string  JSON or null
     */
    public function getProjectsInWorkspace($workspaceId = null, $archived = false)
    {
        $archived = $archived ? 1 : 0;
        $workspaceId = $workspaceId ?: $this->deafultWorkspaceId;

        return $this->askAsana('GET', $this->endPointUrl."projects?archived={$archived}&workspace={$workspaceId}");
    }

    /**
     * This method modifies the fields of a project provided in the request, then returns the full updated record.
     *
     * @param  string $projectId
     * @param  array  $data An array containing fields to update, see Asana API if needed.
     * @return string JSON or null
     */
    public function updateProject($projectId = null, $data)
    {
        $projectId = $projectId ?: $this->deafultProjectId;

        return $this->askAsana('PUT', $this->endPointUrl."projects/{$projectId}", json_encode( array('data' => $data) ));
    }

    /**
     * Returns all unarchived tasks of a given project
     *
     * @param  string $projectId
     * @return string JSON or null
     */
    public function getProjectTasks($projectId = null)
    {
        $projectId = $projectId ?: $this->deafultProjectId;

        return $this->askAsana('GET', $this->endPointUrl."tasks?project={$projectId}");
    }

    /**
     * Returns the list of stories associated with the object.
     * As usual with queries, stories are returned in compact form.
     * However, the compact form for stories contains more
     * information by default than just the ID.
     * There is presently no way to get a filtered set of stories.
     *
     * @param  string $projectId
     * @return string JSON or null
     */
    public function getProjectStories($projectId = null)
    {
        $projectId = $projectId ?: $this->deafultProjectId;

        return $this->askAsana('GET', $this->endPointUrl."projects/{$projectId}/stories");
    }

    /**
     * Adds a comment to a project
     * The comment will be authored by the authorized user, and timestamped when the server receives the request.
     *
     * @param  string $projectId
     * @param  string $text
     * @return string JSON or null
     */
    public function commentOnProject($projectId = null, $text = "")
    {
        $projectId = $projectId ?: $this->deafultProjectId;

        $data = array(
           "text" => $text
        );

        return $this->askAsana('POST', $this->endPointUrl."projects/{$projectId}/stories", json_encode( array('data' => $data) ));
    }

    /**
     * Returns the full record for a single tag.
     *
     * @param  string $tagId
     * @return string JSON or null
     */
    public function getTag($tagId)
    {
        return $this->askAsana('GET', $this->endPointUrl."tags/{$tagId}");
    }

    /**
     * Returns the full record for all tags in all workspaces.
     *
     * @return string JSON or null
     */
    public function getTags()
    {
        return $this->askAsana('GET', $this->endPointUrl.'tags');
    }

    /**
     * Modifies the fields of a tag provided in the request, then returns the full updated record.
     *
     * @param  string $tagId
     * @param  array $data An array containing fields to update, see Asana API if needed.
     * @return string JSON or null
     */
    public function updateTag($tagId, $data)
    {
        return $this->askAsana('PUT', $this->endPointUrl."tags/{$tagId}", json_encode( array('data' => $data) ));
    }

    /**
     * Returns the list of all tasks with this tag. Tasks can have more than one tag at a time.
     *
     * @param  string $tagId
     * @return string JSON or null
     */
    public function getTasksWithTag($tagId)
    {
        return $this->askAsana('GET', $this->endPointUrl."tags/{$tagId}/tasks");
    }

    /**
     * Returns the full record for a single story.
     *
     * @param  string $storyId
     * @return string JSON or null
     */
    public function getSingleStory($storyId)
    {
        return $this->askAsana('GET', $this->endPointUrl."stories/{$storyId}");
    }

    /**
     * Returns all the workspaces.
     *
     * @return string JSON or null
     */
    public function getWorkspaces()
    {
        return $this->askAsana('GET', $this->endPointUrl.'workspaces');
    }

    /**
     * Currently the only field that can be modified for a workspace is its name (as Asana API says).
     * This method returns the complete updated workspace record.
     *
     * @param  array  $data
     * @return string JSON or null
     */
    public function updateWorkspace($workspaceId = null, $data = array("name" => ""))
    {
        $workspaceId = $workspaceId ?: $this->deafultWorkspaceId;

        return $this->askAsana('PUT', $this->endPointUrl."workspaces/{$workspaceId}", json_encode( array('data' => $data) ));
    }

    /**
     * Returns tasks of all workspace assigned to someone.
     * Note: As Asana API says, you must specify an assignee when querying for workspace tasks.
     *
     * @param  string $workspaceId The id of the workspace
     * @param  string $assignee Can be "me" or user ID
     *
     * @return string JSON or null
     */
    public function getWorkspaceTasks($workspaceId = null, $assignee = "me")
    {
        $workspaceId = $workspaceId ?: $this->deafultWorkspaceId;

        return $this->askAsana('GET', $this->endPointUrl."tasks?workspace={$workspaceId}&assignee={$assignee}");
    }

    /**
     * Returns tags of all workspace.
     *
     * @param string $workspaceId The id of the workspace
     * @return string JSON or null
     */
    public function getWorkspaceTags($workspaceId = null)
    {
        $workspaceId = $workspaceId ?: $this->deafultWorkspaceId;

        return $this->askAsana('GET', $this->endPointUrl."workspaces/{$workspaceId}/tags");
    }

    /**
     * Returns users of all workspace.
     *
     * @param  string $workspaceId The id of the workspace
     * @return string JSON or null
     */
    public function getWorkspaceUsers($workspaceId = null)
    {
        $workspaceId = $workspaceId ?: $this->deafultWorkspaceId;

        return $this->askAsana('GET', $this->endPointUrl."workspaces/{$workspaceId}/users");
    }

    /**
     * This function communicates with Asana REST API.
     * You don't need to call this function directly. It's only for inner class working.
     *
     * @param  int    $method
     * @param  string $url
     * @param  string $data Must be a json string
     * @return string JSON or null
     */
    private function askAsana($method, $url, $data = null)
    {
        $this->error = null;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->apiKey}:");
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

        if($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
        }
        else if($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        }

        if(!is_null($data) && ($method == 'POST' || $method == 'PUT')) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        try {
            $return = curl_exec($curl);
            $return = json_decode($return);
        }
        catch(Exception $e) {
            $this->error = $e->getMessage();
            $return = null;
        }

        curl_close($curl);

        return $return;
    }

    /**
     * POST file upload
     *
     * @param  string $filename File to be uploaded
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function addPostFile($filename)
    {
        // Remove leading @ symbol
        if (strpos($filename, '@') === 0) {
            $filename = substr($filename, 1);
        }

        if (!is_readable($filename)) {
            throw new InvalidArgumentException("Unable to open {$filename} for reading");
        }

        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }

        // Use the old style if using an older version of PHP
        return "@{$filename}";
    }
}
