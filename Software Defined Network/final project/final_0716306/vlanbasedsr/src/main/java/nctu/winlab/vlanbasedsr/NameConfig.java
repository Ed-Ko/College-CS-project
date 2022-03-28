package nctu.winlab.vlanbasedsr;

import org.onosproject.core.ApplicationId;
import org.onosproject.net.config.Config;

import java.util.*;
import java.lang.*;

public class NameConfig extends Config<ApplicationId> {

  public static final String name = "name";
  public static final String sr_id = "id";
  public static final String list = "prepare";

  @Override
  public boolean isValid() {
    return hasOnlyFields(sr_id);
  }

  public String name() {
    return get(name, null);
  }

  public String id() {
    return get(sr_id, null);
  }

  //public List<String> prepare() {
  //  return getList(id, null);
  //}


}
