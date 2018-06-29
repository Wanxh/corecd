<?php

use Auth\Auth;
use Myaf\Utils\Arrays;
use Util\Page;


/**
 * Class LogModel
 * @property $id
 * @property $uid
 * @property $username
 * @property $type
 * @property $pid
 * @property $params
 * @property $create_time
 *
 */
class LogModel extends BaseModel
{
    /**
     * 表名
     *
     * @return mixed|string
     */
    public function tableName()
    {
        return "log";
    }

    /**
     * 添加日志
     *
     * @param string $type 日志类型
     * @param int $projectId 项目id
     * @param array $params 日志详情参数
     * @return bool
     */
    public static function add($type, $projectId = 0, $params = [])
    {
        $params = json_encode($params, JSON_UNESCAPED_UNICODE);
        $obj = new LogModel();
        $obj->uid = Auth::userId();
        $obj->username = Auth::userName();
        $obj->type = $type;
        $obj->pid = $projectId;
        $obj->params = $params;
        return $obj->saveWithMsg();
    }


    /**
     * 获取日志列表
     *
     * @param array $search
     * @return array
     */
    public function getPageList($search = [])
    {
        $query = $this->find()->asArray()->order("create_time DESC");
        if ($type = Arrays::get($search, 'type')) {
            $query->andWhere(['type' => $type]);
        }
        if ($uid = Arrays::get($search, 'uid')) {
            $query->andWhere(['uid' => $uid]);
        }

        if ($projectName = Arrays::get($search, 'project_name')) {
            $project = (new ProjectModel())->find()->projectName($projectName)->asArray()->select('id');
            $projectIds = array_column($project, 'id');
            if (!empty($projectIds)) {
                $query->andWhere(['pid' => $projectIds]);
            }
        }
        return Page::pageList($query, $search, '/log/index');
    }

    /**
     * @param $id
     * @return mixed|string
     */
    public function getLogContent($id)
    {
        $obj = $this->findById($id);
        if (empty($obj)) {
            return '[]';
        }
        $params = json_decode($obj->params, true);
        //上线日志显示xml
        if ($obj->type == \Con\Log::PROJECT_ONLINE) {
            return '<pre>' . htmlspecialchars($params['xml']) . '</pre>';
        }

        //其他日志显示格式化后的json
        return '<pre>' . json_encode($params, JSON_PRETTY_PRINT) . '</pre>';
    }

}