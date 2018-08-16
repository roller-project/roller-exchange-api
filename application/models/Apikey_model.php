<?php
class Apikey_Model extends DB_Model{


    public function create_key(){

    }

    
	public function render(){
		return $this->_generate_key();
	}
	/* Helper Methods */
    private function _generate_key()
    {
        do
        {
            // Generate a random salt
            $salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);
            // If an error occurred, then fall back to the previous method
            if ($salt === FALSE)
            {
                $salt = hash('sha256', time() . mt_rand());
            }
            $new_key = substr($salt, 0, config_item('rest_key_length'));
        }
        while ($this->_key_exists($new_key));
        return $new_key;
    }




        /* Private Data Methods */
    private function _get_key($key)
    {

        return $this->db
            ->where(config_item('rest_key_column'), $key)
            ->get(config_item('rest_keys_table'))
            ->row();
    }
    private function _key_exists($key)
    {

        return $this->db
            ->where(config_item('rest_key_column'), $key)
            ->count_all_results(config_item('rest_keys_table')) > 0;
    }
    private function _insert_key($key, $data)
    {
        $data[config_item('rest_key_column')] = $key;
        $data['date_created'] = function_exists('now') ? now() : time();
        return $this->db
            ->set($data)
            ->insert(config_item('rest_keys_table'));
    }
    private function _update_key($key, $data)
    {
        return $this->db
            ->where(config_item('rest_key_column'), $key)
            ->update(config_item('rest_keys_table'), $data);
    }
    private function _delete_key($key)
    {
        return $this->db
            ->where(config_item('rest_key_column'), $key)
            ->delete(config_item('rest_keys_table'));
    }
}