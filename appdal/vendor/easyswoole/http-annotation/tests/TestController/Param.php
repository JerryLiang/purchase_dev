<?php

namespace EasySwoole\HttpAnnotation\Tests\TestController;


use EasySwoole\HttpAnnotation\AnnotationController;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiAuth;
use EasySwoole\HttpAnnotation\AnnotationTag\ApiGroupAuth;
use EasySwoole\HttpAnnotation\Exception\Annotation\ParamValidateError;

/**
 * Class Param
 * @package EasySwoole\HttpAnnotation\Tests\TestController
 * @ApiGroupAuth(name="groupAuth",required="",notEmpty="")
 * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="groupParam",required="",notEmpty="")
 */
class Param extends AnnotationController
{
    /**
     * @ApiAuth(name="onRequestAuth",required="",notEmpty="")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="onRequestParam",required="",notEmpty="")
     */
    public function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action); // TODO: Change the autogenerated stub
    }

    /**
     * @ApiAuth(name="auth",required="",notEmpty="")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="param",required="",notEmpty="")
     */
    public function index()
    {
        $this->response()->write(json_encode($this->request()->getQueryParams()));
    }

    /**
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="foo",lessThanWithColumn="bar")
     */
    public function lessThanWithColumn()
    {

    }

    /**
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="foo",greaterThanWithColumn="bar")
     */
    public function greaterThanWithColumn()
    {

    }

    /**
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="foo",required="",notEmpty="",deprecated=true)
     */
    public function deprecated()
    {

    }

    /**
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="foo",required="",notEmpty="")
     */
    public function notDeprecated()
    {

    }

    /**
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="string",type="string")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="int",type="int")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="float",type="float")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="bool",type="bool")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="json",type="json")
     * @\EasySwoole\HttpAnnotation\AnnotationTag\Param(name="array",type="array")
     */
    public function paramType(string $string, int $int, float $float, bool $bool, \stdClass $json, array $array)
    {
        if (gettype($string) !== 'string' ||
            gettype($int) !== 'integer' ||
            gettype($float) !== 'double' ||
            gettype($bool) !== 'boolean' ||
            gettype($json) !== 'object' ||
            gettype($array) !== 'array'

        ) {
            $this->response()->write('error');
        } else {
            $this->response()->write('success');
        }

    }

    protected function onException(\Throwable $throwable): void
    {
        if ($throwable instanceof ParamValidateError) {
            $validate = $throwable->getValidate();
            $rule = $validate->getError()->getErrorRule();
            if (in_array($rule, ['lessThanWithColumn', 'greaterThanWithColumn',])) {
                throw new \Exception($validate->getError()->__toString());
            }
        }
        parent::onException($throwable); // TODO: Change the autogenerated stub
    }
}
