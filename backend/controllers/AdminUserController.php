<?php
/**
 * Author: lf
 * Blog: https://blog.feehi.com
 * Email: job@feehi.com
 * Created at: 2016-03-31 15:01
 */
namespace backend\controllers;

use yii;
use backend\models\User;
use yii\data\ActiveDataProvider;
use backend\models\AdminRoleUser;
use yii\web\BadRequestHttpException;

class AdminUserController extends BaseController
{

    /**
     * @inheritdoc
     */
    public function getIndexData()
    {
        $query = User::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_ASC,
                ]
            ]
        ]);
        return [
            'dataProvider' => $dataProvider,
        ];
    }

    /**
     * 创建管理员账号
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User();
        $model->setScenario('create');
        $rolesModel = new AdminRoleUser();
        if (yii::$app->getRequest()->getIsPost()) {
            if (
                $model->load(Yii::$app->getRequest()->post())
                && $model->validate()
                && $rolesModel->load(yii::$app->getRequest()->post())
                && $rolesModel->validate()
                && $model->save()
            ) {
                $rolesModel->uid = $model->getPrimaryKey();
                $rolesModel->save();
                Yii::$app->getSession()->setFlash('success', yii::t('app', 'Success'));
                return $this->redirect(['index']);
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                Yii::$app->getSession()->setFlash('error', $err);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'rolesModel' => $rolesModel,
        ]);
    }

    /**
     * 修改管理员账号
     *
     * @param $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $model = $this->getModel($id);
        $model->setScenario('update');
        $rolesModel = AdminRoleUser::findOne(['uid' => $id]);
        if ($rolesModel == null) {
            $rolesModel = new AdminRoleUser();
            $rolesModel->uid = $id;
        }
        if (Yii::$app->getRequest()->getIsPost()) {
            if (
                $model->load(Yii::$app->request->post())
                && $model->validate() && $rolesModel->load(yii::$app->getRequest()->post())
                && $rolesModel->validate()
                && $model->save()
                && $rolesModel->save()
            ) {
                Yii::$app->getSession()->setFlash('success', yii::t('app', 'Success'));
                return $this->redirect(['update', 'id' => $model->getPrimaryKey()]);
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                Yii::$app->getSession()->setFlash('error', $err);
            }
            $model = User::findOne(['id' => yii::$app->getUser()->getIdentity()->getId()]);
        }

        return $this->render('update', [
            'model' => $model,
            'rolesModel' => $rolesModel,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getModel($id = '')
    {
        return User::findOne(['id' => $id]);
    }

    /**
     * 登陆的管理员修改自身
     *
     * @return string
     */
    public function actionUpdateSelf()
    {
        $model = User::findOne(['id' => yii::$app->getUser()->getIdentity()->getId()]);
        $model->setScenario('self-update');
        if (yii::$app->getRequest()->getIsPost()) {
            if ($model->validate() && $model->load(yii::$app->getRequest()->post()) && $model->selfUpdate()) {
                Yii::$app->getSession()->setFlash('success', yii::t('app', 'Success'));
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                Yii::$app->getSession()->setFlash('error', $err);
            }
            $model = User::findOne(['id' => yii::$app->getUser()->getIdentity()->getId()]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * 给管理员分配角色
     *
     * @param string $uid
     * @return string
     */
    public function actionAssign($uid = '')
    {
        $model = AdminRoleUser::findOne(['uid' => $uid]);//->createCommand()->getRawSql();var_dump($model);die;
        if ($model == '') {
            $model = new AdminRoleUser();
        }
        $model->uid = $uid;
        if (yii::$app->getRequest()->getIsPost()) {
            $postRoleId = yii::$app->getRequest()->post(substr(AdminRoleUser::class, strrpos(AdminRoleUser::class,'\\')+1))['role_id'];
            if($model->uid == 1 && ($postRoleId != 1) ) throw new BadRequestHttpException(yii::t('app', "Can not update default super administrator's role"));
            if ($model->load(yii::$app->getRequest()->post()) && $model->save()) {
                Yii::$app->getSession()->setFlash('success', yii::t('app', 'success'));
            } else {
                $errors = $model->getErrors();
                $err = '';
                foreach ($errors as $v) {
                    $err .= $v[0] . '<br>';
                }
                Yii::$app->getSession()->setFlash('error', $err);
            }
        }

        return $this->render('assign', [
            'model' => $model,
        ]);
    }

}