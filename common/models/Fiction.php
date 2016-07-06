<?php

namespace common\models;

use Goutte\Client;
use yii\base\Exception;
use yii\base\Model;
use Yii;
use yii\helpers\ArrayHelper;

class Fiction extends Model
{
    /**
     * 判断指定渠道的指定小说的章节列表能够采集
     * 判断逻辑：是否能够采集到列表，列表长度大于0
     * @param string $ditch_key 渠道key
     * @param string $fiction_key 小说key
     * @return integer
     */
    public static function isFictionRunning($ditch_key, $fiction_key)
    {
        if (isset(Yii::$app->params['ditch'][$ditch_key]['fiction_list'][$fiction_key])) {
            $fiction = Yii::$app->params['ditch'][$ditch_key]['fiction_list'][$fiction_key];
            if ($fiction) {
                $client = new Client();
                $crawler = $client->request('GET', $fiction['fiction_url']);
                try {
                    if ($crawler) {
                        $a = $crawler->filter($fiction['fiction_list_rule']);
                        if ($a && count($a) > 0) {
                            $href = $a->eq(0)->attr('href');
                            if ($href) {
                                if ($fiction['fiction_list_type'] == 'current') {
                                    $url = rtrim($fiction['fiction_url'], '/') . '/' . $href;
                                } else {
                                    //todo 其他渠道不同情况处理
                                    $url = $href;
                                }
                                $crawler = $client->request('GET', $url);
                                if ($crawler) {
                                    $detail = $crawler->filter($fiction['fiction_detail_rule']);
                                    $content = $detail->eq(0);
                                    if ($content && $content->text()) {
                                        return 20;
                                    }
                                }
                            }
                            return 10;
                        }
                    }
                } catch (Exception $e) {
                }
            }
        }
        return 0;
    }

    /**
     * 获取小说的章节列表
     * @param $ditch_key
     * @param $fiction_key
     * @return array
     */
    public static function getFictionList($ditch_key, $fiction_key)
    {
        $array = [];
        if (isset(Yii::$app->params['ditch'][$ditch_key]['fiction_list'][$fiction_key])) {
            $fiction = Yii::$app->params['ditch'][$ditch_key]['fiction_list'][$fiction_key];
            if ($fiction) {
                $cache = Yii::$app->cache;
                $list = $cache->get('ditch_' . $ditch_key . '_fiction_list' . $ditch_key . '_fiction_list');
                if ($list === false || empty($list)) {
                    $client = new Client();
                    $crawler = $client->request('GET', $fiction['fiction_url']);
                    try {
                        if ($crawler) {
                            $a = $crawler->filter($fiction['fiction_list_rule']);
                            if ($a && count($a) > 0) {
                                global $array;
                                $a->each(function ($node) use ($array, $fiction) {
                                    global $array;
                                    if ($node) {
                                        $href = $node->attr('href');
                                        if ($fiction['fiction_list_type'] == 'current') {
                                            $url = rtrim($fiction['fiction_url'], '/') . '/' . $href;
                                        } else {
                                            $url = $href;
                                        }
                                        $text = $node->text();
                                        $array[] = ['href' => $url, 'text' => $text];
                                    }
                                });
                            }
                        }
                    } catch (Exception $e) {
                        //todo
                    }
                    $cache->set('ditch_' . $ditch_key . '_fiction_list' . $fiction_key . '_fiction_list', $array, Yii::$app->params['fiction_chapter_list_cache_expire_time']);
                } else {
                    $array = $list;
                }

            }
        }
        return $array;
    }

    /**
     * 获取指定小说指定章节上上一章、下一章url
     * @param $dk
     * @param $fk
     * @param $url
     * @return array
     */
    public static function getPrevAndNext($dk, $fk, $url)
    {
        $list = self::getFictionList($dk, $fk);
        $urls = ArrayHelper::getColumn($list, 'href');
        if (in_array($url, $urls)) {
            $current = array_search($url, $urls);
        } else {
            $current = false;
        }
        if ($current !== false) {
            return [
                'prev' => ($current - 1 >= 0) ? $list[$current - 1]['href'] : false,
                'next' => ($current + 1 < count($list) - 1) ? $list[$current + 1]['href'] : false
            ];
        } else {
            return [
                'prev' => false,
                'next' => false
            ];
        }
    }

    /**
     * 获取指定章节的title和序号
     * @param $dk
     * @param $fk
     * @param $url
     * @return array
     */
    public static function getFictionTitleAndNum($dk, $fk, $url)
    {
        $list = self::getFictionList($dk, $fk);
        $urls = ArrayHelper::getColumn($list, 'href');
        if (in_array($url, $urls)) {
            $current = array_search($url, $urls);
        } else {
            $current = false;
        }
        if ($current) {
            $title = $list[$current]['text'];
        } else {
            $title = '';
        }
        return ['title' => $title, 'current' => $current];
    }
}