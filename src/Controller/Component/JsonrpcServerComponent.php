<?php
namespace JSONRPCServer\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use Cake\Core\Exception\Exception;

class JsonrpcServerComponent extends Component {
	
	protected $_controller = null;
	
	protected $_version = '2.0';
	
	public function __construct(ComponentRegistry $registry, array $config = []) {
        parent::__construct($registry, $config);
		$this->_controller = $registry->getController();
		$this->_controller->disableAutoRender();
	}
	
	public function startup(Event $event) {
		$controller = $this->_controller;
		$action = strtolower($controller->request->getParam('action'));
		
		if ($action == 'index') {
			if ($controller->request->is('post')) {
				$controller->response = $controller->response->withStatus(200)->withHeader('Content-Type', 'application/json');
				$requests = $this->_processJsonRequest();
				
				if (is_object($requests)) {
					$controller->response = $controller->response->withStringBody(json_encode($this->_parseJsonRequest($requests)));
				} else if (is_array($requests)) {
					$responses = array();
					for ($i = 0; $i < count($requests); $i++) {
						$responses[] = $this->_parseJsonRequest($requests[$i]);
					}
					$controller->response = $controller->response->withStringBody(json_encode($responses));
				} else {
					$controller->response = $controller->response->withStringBody(json_encode($this->_parseJsonRequest($requests)));
				}
			} else {
				$controller->response = $controller->response->withStatus(405);
			}
		}
	}
	
/**
 * Returns a abase JSON-RPC error object.
 *
 * @param int $code The error code.
 * @param string $message The error message.
 * @param int $id The optional JSON-RPC request ID.
 * @return object
 */
	protected function _createJsonError($code, $message, $id = null) {
		$object = new \stdClass();
		$object->jsonrpc = $this->_version;
		$object->error = new \stdClass();
		$object->error->code = $code;
		$object->error->message = $message;
		$object->id = $id;
		return $object;
	}

/**
 * Returns a parse error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createParseError($request = null) {
		return $this->_createJsonError(-32700, 'Parse error', null);
	}

/**
 * Returns a request error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createRequestError($request = null) {
		return $this->_createJsonError(-32600, 'Invalid Request', null);
	}

/**
 * Returns a method error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createMethodError($request = null) {
		return $this->_createJsonError(-32601, 'Method not found', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns a params error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createParamsError($request = null) {
		return $this->_createJsonError(-32602, 'Invalid params', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns an internal error object.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createInternalError($request = null) {
		return $this->_createJsonError(-32000, 'Server error', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns a server error.
 *
 * @param object $request The JSON-RPC request object.
 * @return object
 */
	protected function _createServerError($request = null) {
		return $this->_createJsonError(-32603, 'Internal error', (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Returns an application error.
 *
 * @param object $request The JSON-RPC request object.
 * @param integer $code The error code.
 * @param string $message The error message.
 * @return object
 */
	protected function _createApplicationError($request = null, $code = 0, $message = 'Unknown error') {
		return $this->_createJsonError((int) $code, (string) $message, (isset($request) && is_object($request) && isset($request->id))? $request->id : null);
	}

/**
 * Process the JSON-RPC request.
 *
 * @return array|object|null
 */
	protected function _processJsonRequest() {
		$input = trim($this->_controller->request->input());
		$data = json_decode($input);
		if (is_array($data)) {
			return $data;
		} else if (is_object($data)) {
			return $data;
		} else {
			return null;
		}
	}

/**
 * Parses a JSON-RPC request object.
 *
 * @param object $request The JSON-RPC request object.
 * @return array
 */
	protected function _parseJsonRequest($request) {
		if (!is_object($request)) {
			return $this->_createParseError($request);
		}
		if (!isset($request->jsonrpc) || !is_string($request->jsonrpc) || $request->jsonrpc !== $this->_version) {
			return $this->_createRequestError($request);
		}
		if (!isset($request->method) || !is_string($request->method) || !method_exists($this->_controller, $request->method)) {
			return $this->_createMethodError($request);
		}
		if (isset($request->params) && !is_array($request->params) && !is_object($request->params)) {
			return $this->_createParamsError($request);
		}
		try {
			ob_start();
			$result = call_user_func_array(array($this->_controller, $request->method), array($request));
			ob_end_clean();
			return $this->_processJsonResponse($request, $result);
		} catch(Exception $e) {
		
			return $this->_createApplicationError($request, $e->getCode(), $e->getMessage());
		}
	}

/**
 * Returns a JSON-RPC response object.
 *
 * @param object $request The JSON-RPC request object.
 * @param mixed $result The result to send.
 * @return object
 */
	protected function _processJsonResponse($request, $result) {
		$object = new \stdClass();
		$object->jsonrpc = $this->_version;
		$object->result = $result;
		$object->id = (isset($request) && is_object($request) && isset($request->id))? $request->id : null;
		return $object;
	}

}

